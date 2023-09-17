<?php

namespace Rulezdev\RulezbotBundle\Exception;

class ModuleRuntimeException extends \RuntimeException
{
    public function __construct(string $message, public readonly bool $isClearWorkflow = false)
    {
        parent::__construct($message, 0, null);
    }
}