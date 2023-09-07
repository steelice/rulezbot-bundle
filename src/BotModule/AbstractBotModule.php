<?php

namespace Rulezdev\RulezbotBundle\BotModule;

use Rulezdev\RulezbotBundle\Helper\TgCallbackHelper;
use Rulezdev\RulezbotBundle\Entity\BotModule;
use Rulezdev\RulezbotBundle\Entity\ChatLog;
use Rulezdev\RulezbotBundle\Service\ChatContainer;
use Rulezdev\RulezbotBundle\Service\WorkflowService;

abstract class AbstractBotModule implements BotModuleInterface
{
    protected static array $supportTypes = [ChatLog::TYPE_TEXT];

    protected const DEFAULT_CONFIG = [];

    protected BotModule $module;
    public ChatContainer $tg;
    public WorkflowService $workflow;

    public static function getAlias(): string
    {
        return strtolower(preg_replace('/^.+\\\(.+)Module$/i', '$1', static::class));
    }

    public static function getDefaultSettings(): array
    {
        return [];
    }

    public static function isAvailable(): bool
    {
        return true;
    }

    public static function getIcon(): ?string
    {
        return null;
    }

    /**
     * Обрабатывает запрос внутри модуля
     */
    public function processRequest(ChatContainer $tg): bool
    {
        return false;
    }

    public function processCallback(TgCallbackHelper $cbHelper): bool
    {
        return false;
    }

    public static function getPriority(): int
    {
        return 1000;
    }

    /**
     * Возвращает, поддерживает ли данный модуль тип этого обновления
     */
    public static function isSupport(string $type): bool
    {
        return in_array($type, static::$supportTypes, true);
    }

    /**
     * Предварительная проверка, поддерживает ли этот модуль данный апдейт,
     * необходимо для экономии создания кучи классов на каждое сообщение
     */
    public static function checkPrecondition(ChatContainer $tg): bool
    {
        return true;
    }

    /**
     * Возвращает данные из конфига
     */
    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return self::DEFAULT_CONFIG[$key] ?? $default;
    }

    public static function getCommands(): array
    {
        return [];
    }

    /**
     * @param BotModule $module
     * @return AbstractBotModule
     */
    public function setModule(BotModule $module): static
    {
        $this->module = $module;

        return $this;
    }

    public function configure(
        BotModule $module,
        ChatContainer $tg,
        WorkflowService $workflow,
    ): static
    {
        $this->setModule($module);
        $this->tg = $tg;
        $this->workflow = $workflow;

        return $this;
    }
}