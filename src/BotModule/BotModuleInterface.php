<?php

namespace Rulezdev\RulezbotBundle\BotModule;

interface BotModuleInterface
{
    /**
     * Возвращает имя модуля
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Возвращает описание модуля
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Возвращает короткое имя модуля
     *
     * @return string
     */
    public static function getAlias(): string;

    /**
     * Возвращает дефолтную структуру настроек
     *
     * @return array
     */
    public static function getDefaultSettings(): array;

    /**
     * Возвращает доступность модуля как такового
     *
     * @return bool
     */
    public static function isAvailable(): bool;

    /**
     * Возвращает иконку для модуля (класс для i)
     */
    public static function getIcon(): ?string;

    public static function getPriority(): int;
}