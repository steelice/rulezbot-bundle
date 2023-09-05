<?php

namespace Rulezdev\RulezbotBundle\Entity;

interface HasFileEntityInterface
{
    public function getDirUDID(): string;

    public static function getEntityType(): string;
}