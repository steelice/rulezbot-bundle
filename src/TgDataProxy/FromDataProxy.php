<?php

namespace Rulezdev\RulezbotBundle\TgDataProxy;

use TelegramBot\Api\Types\User;

class FromDataProxy
{
    public function __construct(protected User $user)
    {
    }

    public function getUsername(): string
    {
        return $this->user->getUsername();
    }

    public function getId(): int
    {
        return $this->user->getId();
    }

    public function getName(): string
    {
        return implode(' ', [
            $this->user->getFirstName(),
            $this->user->getLastName(),
        ]);
    }

    public function isBot(): bool
    {
        return $this->user->isBot() ?? false;
    }

    public function getOriginal(): User
    {
        return $this->user;
    }
}