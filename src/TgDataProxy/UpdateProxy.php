<?php

namespace Rulezdev\RulezbotBundle\TgDataProxy;

use Rulezdev\RulezbotBundle\Entity\ChatLog;
use TelegramBot\Api\Types\Update;

class UpdateProxy
{
    public readonly ?MessageDataProxy $message;
    public readonly bool $isEdited;
    public readonly bool $isUpdateSupported;

    public function __construct(public readonly Update $update)
    {
        $isUpdateSupported = true;
        $isEdited = false;
        if ($this->update->getMessage()) {
            $this->message = new MessageDataProxy($this->update->getMessage());
        } elseif ($this->update->getCallbackQuery()?->getMessage()) {
            $this->message = new MessageDataProxy($this->update->getCallbackQuery()->getMessage());
        } elseif ($this->update->getEditedMessage()){
            $this->message = new MessageDataProxy($this->update->getEditedMessage(), true);
            $isEdited = true;
        } else {
            $this->message = null;
            $isUpdateSupported = false;
        }

        $this->isEdited = $isEdited;
        $this->isUpdateSupported = $isUpdateSupported;
    }

    public function getType(): string
    {
        if ($this->message) {
            return $this->message->getType();
        }

        return match (true) {
            $this->update->getCallbackQuery()?->getMessage() !== null => ChatLog::TYPE_CALLBACK,
            default => ChatLog::TYPE_UNKNOWN
        };
    }

    public function getText(): string
    {
        if ($this->message) {
            return $this->message->getText();
        }

        return match (true) {
            default => $this->update->toJson()
        };
    }

    public function getNormalizedText(bool $lower): string
    {
        return $this->message ? $this->message->getNormalizedText($lower) : '';
    }

    public function getOnlyText(bool $lower): string
    {
        return $this->message ? $this->message->getOnlyText($lower) : '';
    }

    public function getMessageId(): ?int
    {
        return $this->message?->getId();
    }

}