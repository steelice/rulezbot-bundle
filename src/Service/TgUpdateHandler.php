<?php

namespace Rulezdev\RulezbotBundle\Service;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Rulezdev\RulezbotBundle\BotModule\AbstractBotModule;
use Rulezdev\RulezbotBundle\BotModule\BotModuleList;
use Rulezdev\RulezbotBundle\Entity\BotModule;
use Rulezdev\RulezbotBundle\Entity\ChatLog;
use Rulezdev\RulezbotBundle\Exception\ModuleRuntimeException;
use Rulezdev\RulezbotBundle\Helper\TgCallbackHelper;
use Rulezdev\RulezbotBundle\Repository\BotInChatRepository;
use Rulezdev\RulezbotBundle\Repository\ChatRepository;
use Rulezdev\RulezbotBundle\Repository\UserInChatRepository;
use Rulezdev\RulezbotBundle\Repository\UserRepository;
use Rulezdev\RulezbotBundle\TgDataProxy\ChatDataProxy;
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
            $updateProxy = new UpdateProxy($update);

            $message = $update->getMessage();

            $this->logger->info('Got update:', ['update' => $update, 'message' => $update->getMessage()]);

            if ($update->getEditedMessage()) {
                $message = $update->getEditedMessage();
            }

            if ($update->getCallbackQuery()) {
                return $this->processCallback($update);
            }

            if ($update->getPreCheckoutQuery()) {
                return $this->processPreCheckoutQuery($update);
            }

            if ($updateProxy->getType() === ChatLog::TYPE_UNKNOWN) {
                $this->logger->warning('Message type unknown!');

                return false;
            }

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
                try {
                    return $this->processModule($wfModule);
                } catch (Throwable $e) {
                    $this->chatContainer->reply('Some error');
                    $this->logger->critical('Error in module', ['error_in_module' => $wfModule->getClassName(), 'exception' => $e]);

                    return false;
                }
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

        $chat = $this->chatRepository->getChatByTgChat(new ChatDataProxy($update->getCallbackQuery()->getMessage()->getChat()));
        $user = $this->userRepository->getOrCreateUser($update->getCallbackQuery()->getFrom());

        $userInChat = $this->userInChatRepository->getOrCreate($user, $chat);

        $this->workflow->init($userInChat);

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
        $this->chatContainer->setChat($chat)->setUser($user)->setUserInChat($userInChat);

        /** @var AbstractBotModule $worker */
        $worker = $this->container->get($className);
        $worker->configure($module, $this->chatContainer, $this->workflow);

        $callbackMethod = 'callback_' . $callbackHelper->getMethod();

        if (method_exists($worker, $callbackMethod)) {
            return $worker->$callbackMethod($callbackHelper);
        }

        if (!method_exists($worker, 'processCallback')) {
            $this->logger->error('Class ' . $className . ' not support callbacks!');

            return false;
        }

        return $worker->processCallback($callbackHelper);
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

            if ($this->workflow->getModule() && ($stage = $this->workflow->getStage())) {
                $stageMethod = 'processRequest_' . $stage;
                $this->logger->info('Module ' . $className . ' try to exec stage ' . $stageMethod . '...');
                if (method_exists($worker, $stageMethod)) {
                    $result = $worker->$stageMethod();
                    if (!$result) {
                        $this->logger->debug('Module ' . $className . ' is try to find answer, but cant');

                        return false;
                    }

                    return $result;
                } else {
                    $this->logger->warning('Module ' . $className . ' stageMethod ' . $stageMethod . ' does not exists!');

                }
            }

            if ($this->chatContainer->update->isEdited && method_exists($worker, 'processEditedMessage')) {
                $result = $worker->processEditedMessage();
                if (!$result) {
                    $this->logger->debug('Module ' . $className . ' is try to find answer for edited message, but cant');

                    return false;
                }

                return $result;
            }

            $result = $worker->processRequest();
            if (!$result) {
                $this->logger->debug('Module ' . $className . ' is try to find answer, but cant');
            }
        } catch (ModuleRuntimeException $e) {
            $this->logger->critical('Error in module', ['error_in_module' => $className, 'exception' => $e]);
            if ($e->isClearWorkflow) {
                $this->workflow->clear();
            }

            $this->chatContainer->reply($e->getMessage());

            return false;
        } catch (Throwable $e) {
            $this->logger->critical('Error in module', ['error_in_module' => $className, 'exception' => $e]);

            return false;
        }

        if ($this->em->isOpen()) {
            $this->em->flush();
        }

        return $result;
    }

    public static function getSubscribedServices(): array
    {
        return BotModuleList::getClassList(__DIR__ . '/../BotModule');
    }
}