<?php

namespace Rulezdev\RulezbotBundle\Message\Telegram;

class Photo
{
    public function __construct(
        public int          $chatId,
        public string|array $photo,
        public ?string      $caption = null,
        public ?int         $replyToMessageId = null,
        public              $replyMarkup = null,
        public bool         $disableNotification = false,
        public ?string      $parseMode = null,
        public ?bool        $deleteAfterSend = null,
    )
    {
    }
}