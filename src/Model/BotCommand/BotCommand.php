<?php

namespace Rulezdev\RulezbotBundle\Model\BotCommand;

class BotCommand
{
    public function __construct(
        public string $command,
        public string $description,
        public string $scope = BotCommandScope::DEFAULT,
    )
    {
    }
}