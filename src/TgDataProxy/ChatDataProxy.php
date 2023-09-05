<?php

namespace Rulezdev\RulezbotBundle\TgDataProxy;

use TelegramBot\Api\Types\Chat;

class ChatDataProxy
{
    public function __construct(protected Chat $chat)
    {
    }

    public function getUsername(): string
    {
        return $this->chat->getUsername();
    }

    public function getId(): int
    {
        return $this->chat->getId();
    }

    public function getName(): string
    {
        if ($this->isPrivate()) {
            return implode(' ', [
                $this->chat->getFirstName(),
                $this->chat->getLastName(),
            ]);
        }

        return $this->chat->getTitle();
    }

    public function getType(): string
    {
        return $this->chat->getType();
    }

    public function isPrivate(): bool
    {
        return $this->getType() === 'private';
    }
}