<?php

namespace Rulezdev\RulezbotBundle\MessageHandler\Telegram;

use Rulezdev\RulezbotBundle\Message\Telegram\Animation;
use Rulezdev\RulezbotBundle\Service\BotService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use TelegramBot\Api\HttpException;
use function Sentry\captureMessage;

#[AsMessageHandler]
class MessageAnimationHandler
{
    public function __construct(private readonly BotService $bot)
    {
    }

    public function __invoke(Animation $msg): void
    {
        if (is_array($msg->animation)) {
            shuffle($msg->animation);
            $msg->animation = $msg->animation[0];
        }

        try {
            $this->bot->api->sendAnimation(
                $msg->chatId,
                $msg->animation,
                null,
                $msg->caption,
                null,
                $msg->replyToMessageId,
                $msg->replyMarkup,
                $msg->disableNotification,
                $msg->parseMode,
            );
        } catch (HttpException $e) {
            if (str_contains($e->getMessage(), 'wrong file identifier')) {
                captureMessage('Wrong animation: ' . $msg->animation);

                return;
            }
            throw $e;
        }
    }
}