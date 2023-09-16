<?php

namespace Rulezdev\RulezbotBundle\Service;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Rulezdev\RulezbotBundle\BotModule\AbstractBotModule;
use Rulezdev\RulezbotBundle\BotModule\BotModuleList;
use Rulezdev\RulezbotBundle\Entity\BotModule;
use Rulezdev\RulezbotBundle\Exception\ModuleRuntimeException;
use Rulezdev\RulezbotBundle\Helper\TgCallbackHelper;
use Rulezdev\RulezbotBundle\Repository\BotInChatRepository;
use Rulezdev\RulezbotBundle\Repository\ChatRepository;
use Rulezdev\RulezbotBundle\Repository\UserInChatRepository;
use Rulezdev\RulezbotBundle\Repository\UserRepository;
use Rulezdev\RulezbotBundle\TgDataProxy\UpdateProxy;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Update;
use Throwable;

class TgUpdateHandler implements ServiceSubscriberInterface
{
    public function __construct(
        protected readonly BotService             $botService,
        protected readonly UserRepository         $userRepository,
        protected readonly ChatRepository         $chatRepository,
        protected readonly ChatLogService         $chatLogService,
        protected readonly LoggerInterface        $logger,
        protected readonly UserInChatRepository   $userInChatRepository,
        protected readonly BotInChatRepository    $botsInChatsRepository,
        protected readonly ModuleService          $moduleService,
        protected readonly BotModuleList          $botModuleList,
        protected readonly ChatContainer          $chatContainer,
        protected readonly ContainerInterface     $container,
        protected readonly WorkflowService        $workflow,
        protected readonly EntityManagerInterface $em,
    )
    {
    }

    public function handleRequest(): ?bool
    {
        $result = null;

        $this->botService->getClient()->on(function (Update $update) use (&$result) {
            $message = $update->getMessage();

            $this->logger->info('Got update:', ['update' => $update, 'message' => $update->getMessage()]);

            if ($update->getCallbackQuery()) {
                return $this->processCallback($update);
            }

            if ($update->getPreCheckoutQuery()) {
                return $this->processPreCheckoutQuery($update);
            }

            if (!$message instanceof Message) {
                $this->logger->warning('Message type unknown!');

                return false;
            }

            $updateProxy = new UpdateProxy($update);

            $chat = $this->chatRepository->getChatByMessage($updateProxy->message);
            $this->chatRepository->updateChatStats($chat, $updateProxy);
            $user = $this->userRepository->getOrCreateUser($message->getFrom());

            $userInChat = $this->userInChatRepository->getOrCreate($user, $chat);
            $botInChat = $this->botsInChatsRepository->getOrCreate($this->botService->getEntity(), $chat);
            $botInChat->setLastActivity(new DateTimeImmutable());
            if (!$botInChat->getIsActive()) {
                $botInChat->setIsActive(true);
            }

            $logMessage = $this->chatLogService->logMessage($userInChat, $updateProxy);
            $this->workflow->init($userInChat);
            $this->chatContainer->fill($updateProxy, $chat, $user, $userInChat, $logMessage);

            if ($wfModule = $userInChat->getWorkflowModule()) {
                return $this->processModule($wfModule);
            }

            $modules = $this->moduleService->modulesForChat($chat);
            foreach ($modules as $module) {
                if (!$module->getIsEnabled() || !$this->isModuleSupport($module->getModule(), $updateProxy)) {
                    continue;
                }
                $result = $this->processModule($module->getModule());
                if ($result) {
                    return true;
                }
            }

            return false;
        }, function () {
            return true;
        });

        $this->botService->getClient()->run();

        return $result;
    }

    public function setUpProxy(): void
    {
        dump($this->botService->api->getWebhookInfo());
        $result = $this->botService->resetWebhook();
        dump($result);
    }

    /**
     * Обработка callbacks для модулей
     *
     */
    protected function processCallback(Update $update): bool
    {
        $callbackHelper = new TgCallbackHelper(
            new UpdateProxy($update),
            $this->botService
        );

        if (!$callbackHelper->getModuleName()) {
            return false;
        }

        $className = 'App\BotModule\\' . $callbackHelper->getModuleName() . 'Module';
        if (!class_exists($className)) {
            $this->logger->error('Class ' . $className . ' not exists!', ['callback' => $update->getCallbackQuery()]);

            return false;
        }

        $module = $this->moduleService->getModuleRepository()->getModule($callbackHelper->getModuleName(), $className);
        if (!$module) {
            $this->logger->error('Module ' . $callbackHelper->getModuleName() . ' not exists!', [
                'update' => $update
            ]);

            return false;
        }

        /** @var AbstractBotModule $worker */
        $worker = $this->container->get($className);
        $worker->configure($module, $this->chatContainer, $this->workflow);

        $callbackMethod = 'callback_' . $callbackHelper->getMethod();

        if (method_exists($module, $callbackMethod)) {
            return $module->$callbackMethod($callbackHelper);
        }

        if (!method_exists($module, 'processCallback')) {
            $this->logger->error('Class ' . $className . ' not support callbacks!');

            return false;
        }

        return $module->processCallback($callbackHelper);
    }

    private function processPreCheckoutQuery(Update $update): bool
    {
        $preCheckoutQuery = $update->getPreCheckoutQuery();
        $this->logger->info('Got pre checkout query', ['pre_checkout_query' => $preCheckoutQuery]);

        $data = json_decode($preCheckoutQuery->getInvoicePayload(), true);
        $this->logger->info('Got pre checkout query data', ['data' => $data]);

        if (empty($data['chatId'])) {
            $this->logger->error('Got pre checkout query without chatId', ['pre_checkout_query' => $preCheckoutQuery]);
            $this->botService->api->answerPreCheckoutQuery($preCheckoutQuery->getId(), false, 'Не удалось оплатить услугу');

            return false;
        }

        if ($this->botService->api->answerPreCheckoutQuery($preCheckoutQuery->getId())) {
            $this->logger->info('Answered pre checkout query', ['pre_checkout_query' => $preCheckoutQuery]);
        } else {
            $this->logger->error('Failed to answer pre checkout query', ['pre_checkout_query' => $preCheckoutQuery]);
        }

        return true;
    }

    protected function isModuleSupport(
        BotModule   $module,
        UpdateProxy $update
    ): bool
    {
        $className = $module->getClassName();

        try {
            if (!class_exists($className) || !method_exists($className, 'isSupport')) {
                $this->logger->error('Class not exists or not bot module', ['class' => $className]);

                return false;
            }
        } catch (Throwable) {
            $this->logger->error('Class not exists or not bot module', ['class' => $className]);

            return false;
        }

        if (!$className::isSupport($update->getType()) || !$className::checkPrecondition($this->chatContainer)) {
            return false;
        }

        return true;
    }

    protected function processModule(
        BotModule $module,
    ): bool
    {
        $className = $module->getClassName();

        try {
            /** @var AbstractBotModule $worker */
            $worker = $this->container->get($className);
            $worker->configure($module, $this->chatContainer, $this->workflow);

            $result = $worker->processRequest($this->chatContainer);
            if (!$result) {
                $this->logger->debug('Module ' . $className . ' is try to find answer, but cant');
            }
        } catch (ModuleRuntimeException $e) {
            $this->logger->critical('Error in module', ['error_in_module' => $className, 'exception' => $e]);

            $this->chatContainer->reply($e->getMessage());

            return false;
        } catch (Throwable $e) {
            $this->logger->critical('Error in module', ['error_in_module' => $className, 'exception' => $e]);

            return false;
        }

        $this->em->flush();

        return $result;
    }

    public static function getSubscribedServices(): array
    {
        return BotModuleList::getClassList(__DIR__ . '/../BotModule');
    }
}