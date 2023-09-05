<?php

namespace Rulezdev\RulezbotBundle\Model\BotCommand;

use TelegramBot\Api\Types\BotCommandScope\BotCommandScopeAllChatAdministrators;
use TelegramBot\Api\Types\BotCommandScope\BotCommandScopeAllGroupChats;
use TelegramBot\Api\Types\BotCommandScope\BotCommandScopeAllPrivateChats;
use TelegramBot\Api\Types\BotCommandScope\BotCommandScopeChat;
use TelegramBot\Api\Types\BotCommandScope\BotCommandScopeDefault;

class BotCommandScope
{
    public const DEFAULT = 'default';
    public const ALL_PRIVATE_CHATS = 'all_private_chats';

    public const ALL_GROUP_CHATS = 'all_group_chats';

    public const ALL_CHAT_ADMINISTRATORS = 'all_chat_administrators';

    public const CHAT = 'chat';

    public const CHAT_ADMINISTRATORS = 'chat_administrators';

    public const CHAT_MEMBER = 'chat_member';

    public const DEFAULTS = [
        self::DEFAULT,
        self::ALL_PRIVATE_CHATS,
        self::ALL_GROUP_CHATS,
        self::ALL_CHAT_ADMINISTRATORS,
    ];

    protected array $commands = [];

    public static function getScopeJson(string $scope, ?int $chatId = null, ?int $userId = null): string
    {
        return match ($scope) {
            self::CHAT, self::CHAT_ADMINISTRATORS => json_encode(['type' => $scope, 'chat_id' => $chatId]),
            self::CHAT_MEMBER => json_encode(['type' => $scope, 'chat_id' => $chatId, 'user_id' => $userId]),
            default => json_encode(['type' => $scope]),
        };
    }

    public static function getScopeObject(string $scope, ?int $chatId = null, ?int $userId = null): BotCommandScopeDefault
    {
        return match ($scope) {
            self::CHAT => new BotCommandScopeChat($chatId),
            self::CHAT_ADMINISTRATORS => new BotCommandScopeAllChatAdministrators(),
            self::ALL_PRIVATE_CHATS => new BotCommandScopeAllPrivateChats(),
            self::ALL_GROUP_CHATS => new BotCommandScopeAllGroupChats(),
            default => new BotCommandScopeDefault(),
        };
    }

    public function __construct()
    {
    }

    public function addCommand(BotCommand $command): self
    {
        if (isset($this->commands[$command->scope])) {
            $this->commands[$command->scope][] = $command;
        } else {
            $this->commands[$command->scope] = [$command];
        }

        return $this;
    }

    public function addCommands(array $commands): self
    {
        foreach ($commands as $command) {
            $this->addCommand($command);
        }

        return $this;
    }

    /**
     * @return array[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }
}