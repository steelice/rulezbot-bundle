<?php

namespace Rulezdev\RulezbotBundle\Helper;

use Rulezdev\RulezbotBundle\Service\BotService;
use Rulezdev\RulezbotBundle\TgDataProxy\UpdateProxy;

class TgCallbackHelper
{
    public readonly array $callbackData;

    public function __construct(
        public readonly UpdateProxy $update,
        public readonly BotService  $botService,
    )
    {
        $this->callbackData = explode(':', $this->update->update->getCallbackQuery()->getData());
    }

    public function getModuleName()
    {
        return $this->callbackData[0];
    }

    public function getMethod()
    {
        return $this->callbackData[1];
    }

    public function getValue($key)
    {
        return $this->callbackData[$key] ?? null;
    }
}