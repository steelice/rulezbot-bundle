<?php

namespace Rulezdev\RulezbotBundle\Message\Telegram;

class Sticker
{
    public function __construct(
        public int          $chatId,
        public string|array $sticker,
        public ?int         $replyToMessageId = null,
        public              $replyMarkup = null,
        public bool         $disableNotification = false
    )
    {
    }
}