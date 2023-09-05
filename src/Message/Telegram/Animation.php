<?php

namespace Rulezdev\RulezbotBundle\Message\Telegram;

class Animation
{
    public function __construct(
        public int     $chatId,
        public string  $animation,
        public ?string $caption = null,
        public ?int    $replyToMessageId = null,
        public         $replyMarkup = null,
        public bool    $disableNotification = false,
        public ?string $parseMode = null,
    )
    {
    }
}