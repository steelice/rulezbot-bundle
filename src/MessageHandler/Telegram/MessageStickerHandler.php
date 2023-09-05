<?php

namespace Rulezdev\RulezbotBundle\MessageHandler\Telegram;

use Rulezdev\RulezbotBundle\Message\Telegram\Sticker;
use Rulezdev\RulezbotBundle\Service\BotService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class MessageStickerHandler
{
    public function __construct(private readonly BotService $bot)
    {
    }

    public function __invoke(Sticker $msg)
    {
        if (is_array($msg->sticker)) {
            shuffle($msg->sticker);
            $msg->sticker = $msg->sticker[0];
        }

        $this->bot->api->sendSticker(
            $msg->chatId,
            $msg->sticker,
            null,
            $msg->replyToMessageId,
            $msg->replyMarkup,
            $msg->disableNotification
        );
    }
}