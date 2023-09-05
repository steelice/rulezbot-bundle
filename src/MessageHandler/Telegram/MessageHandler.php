<?php

namespace Rulezdev\RulezbotBundle\MessageHandler\Telegram;

use Rulezdev\RulezbotBundle\Message\Telegram\Message;
use Rulezdev\RulezbotBundle\Service\BotService;
use Rulezdev\RulezbotBundle\Repository\BotInChatRepository;
use Rulezdev\RulezbotBundle\Repository\ChatRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use TelegramBot\Api\Exception;
use TelegramBot\Api\HttpException;
use TelegramBot\Api\InvalidArgumentException;

#[AsMessageHandler]
class MessageHandler
{
    public function __construct(
        private readonly BotService          $bot,
        private readonly ChatRepository      $chatRepository,
        private readonly BotInChatRepository $inChatsRepository
    )
    {
    }

    /**
     * @throws Exception
     * @throws HttpException
     * @throws InvalidArgumentException
     */
    public function __invoke(Message $msg)
    {
        if (is_array($msg->text)) {
            shuffle($msg->text);
            $msg->text = $msg->text[0];
        }

        $chat = $this->chatRepository->findOneBy(['msgChatId' => $msg->chatId]);
        if (!$chat) {
            throw new \LogicException('Chat not found: ' . $msg->chatId);
        }

        $botInChat = $this->inChatsRepository->getOrCreate($this->bot->getEntity(), $chat);

        if (!$botInChat->getIsActive()) {
            return;
        }

        try {
            $this->bot->api->sendMessage($msg->chatId, $msg->text, $msg->parseMode, $msg->disablePreview,
                null, $msg->replyToMessageId, $msg->replyMarkup, $msg->disableNotification);
        } catch (HttpException $e) {
            if (str_contains($e->getMessage(), 'rights to send')) {
                $botInChat->setIsActive(false);
                $this->inChatsRepository->save($botInChat);

                return;
            } elseif (str_contains($e->getMessage(), 'the entity starting')) {
                \Sentry\captureException($e);
                \Sentry\captureMessage('Wrong message: ' . $msg->text);

                return;
            } elseif (str_contains($e->getMessage(), 'message not found')) {
                return;
            }

            throw $e;
        }
    }
}