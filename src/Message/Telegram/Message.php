<?php

namespace Rulezdev\RulezbotBundle\Message\Telegram;

use TelegramBot\Api\BaseType;

class Message
{
    public $chatId;
    /**
     * @var string
     */
    public $text;
    /**
     * @var null
     */
    public $parseMode;
    /**
     * @var bool
     */
    public $disablePreview;
    /**
     * @var null
     */
    public $replyToMessageId;
    /**
     * @var null
     */
    public $replyMarkup;
    /**
     * @var bool
     */
    public bool $disableNotification;

    public function __construct(
        $chatId,
        string|array $message,
        $parseMode = null,
        bool $disablePreview = false,
        $replyToMessageId = null,
        ?BaseType $replyMarkup = null,
        bool $disableNotification = false
    )
    {
        $this->chatId = $chatId;
        $this->text = $message;
        $this->parseMode = $parseMode;
        $this->disablePreview = $disablePreview;
        $this->replyToMessageId = $replyToMessageId;
        $this->replyMarkup = $replyMarkup;
        $this->disableNotification = $disableNotification;
    }
}