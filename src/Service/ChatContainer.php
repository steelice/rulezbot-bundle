<?php

namespace Rulezdev\RulezbotBundle\Service;

use Exception;
use Rulezdev\RulezbotBundle\Entity\Chat;
use Rulezdev\RulezbotBundle\Entity\ChatLog;
use Rulezdev\RulezbotBundle\Entity\User;
use Rulezdev\RulezbotBundle\Entity\UserInChat;
use Rulezdev\RulezbotBundle\TgDataProxy\MessageDataProxy;
use Rulezdev\RulezbotBundle\TgDataProxy\UpdateProxy;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\BaseType;

class ChatContainer
{
    public UpdateProxy $update;
    public Chat $chat;
    public User $user;
    public UserInChat $userInChat;
    public ChatLog $logMessage;

    protected string $parseMode = BotService::PARSE_MODE_MARKDOWN;

    public function __construct(
        private readonly BotService          $botService,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function fill(
        UpdateProxy $update,
        Chat        $chat,
        User        $user,
        UserInChat  $userInChat,
        ChatLog     $logMessage
    ): void
    {
        $this->update = $update;
        $this->chat = $chat;
        $this->user = $user;
        $this->userInChat = $userInChat;
        $this->logMessage = $logMessage;

        $this->botService->setInitialMessage($this->logMessage);
    }

    public function getNormalizedText(bool $lower = true): string
    {
        return $this->update->getNormalizedText($lower);
    }

    public function getOnlyText(bool $lower = true): string
    {
        return $this->update->getOnlyText($lower);
    }

    public function trans(string $text, array $params = []): string
    {
        return $this->translator->trans(
            $text,
            $params,
            null,
            $this->user->getLang()
        );
    }

    /**
     * Проверяет, начинается ли текст сообщения с указанной команды или ключевого слова.
     * Если да, то возвращает это вхождение в виде строки
     */
    public function matchFullKeyword(array|string $kws, int $kwNumber = 1, bool $isMultiline = false): ?string
    {
        if (!is_array($kws)) {
            $kws = [$kws];
        }

        $text = $this->getNormalizedText();

        $modifiers = 'iu';
        if ($isMultiline) {
            $modifiers .= 'ms';
        }

        foreach ($kws as $kw) {
            if (preg_match(sprintf('~^(%s)$~%s', $kw, $modifiers), $text, $m)) {
                return $m[$kwNumber];
            }
        }

        return null;
    }

    public function reply(
        string|array $text,
        ?BaseType    $replyMarkup = null,
        bool         $disablePreview = false
    ): bool
    {
        return $this->botService->sendMessage($this->chat->getMsgChatId(), $text, $this->parseMode, null, $replyMarkup, $disablePreview);
    }

    public function replyTranslated(
        string|array $text,
        array        $params = [],
        ?BaseType    $replyMarkup = null,
        bool         $disablePreview = false
    ): bool
    {
        return $this->reply($this->trans($text, $params), $replyMarkup, $disablePreview);
    }

    public function replySticker(
        string|array $sticker,
                     $replyMarkup = null,
        bool         $disablePreview = false
    ): bool
    {
        return $this->botService->sendSticker($this->chat->getMsgChatId(), $sticker, null, $replyMarkup, $disablePreview);
    }

    public function match(string $string): ?array
    {
        if (preg_match($string, $this->getNormalizedText(), $m)) {
            return $m;
        }

        return null;
    }

    public function replyToMessage(array|string $msg, $replyMarkup = null, $disablePreview = false): bool
    {
        return $this->botService->sendMessage(
            $this->chat->getMsgChatId(),
            $msg,
            $this->parseMode,
            $this->update->message->getId(),
            $replyMarkup,
            $disablePreview
        );
    }

    public function isPrivateRequest(): bool
    {
        if (!$this->update->message) {
            return false;
        }

        return $this->update->message->chat->isPrivate();
    }

    /**
     * Возвращает кликабельного пользователя
     */
    public static function linkableUsername(User|UserInChat $user): string
    {
        if ($user instanceof UserInChat) {
            $how2call = $user->getHow2call() ?: $user->getUser()->getHow2call();
            $id = $user->getUser()->getMsgId();
        } else {
            $how2call = $user->getHow2call();
            $id = $user->getMsgId();
        }

        return sprintf("[%s](tg://user?id=%s)", $how2call, $id);
    }

    /**
     * Проверяет, написано ли сообщение в ответ кому-либо.
     *
     * @param bool $allowBot Считать ли ответы от ботов
     * @return bool
     */
    public function isReplyToOther(bool $allowBot = false): bool
    {
        if ($this->isPrivateRequest()) {
            return false;
        }

        if (!$replyTo = $this->update->message->replyToMessage) {
            return false;
        }

        if (!$allowBot && $replyTo->from->isBot()) {
            return false;
        }

        return ($replyTo->from->getId() !== $this->update->message->from->getId());
    }

    /**
     * Хелпер для возвращения вероятности «в 3х из 100»
     * @throws Exception
     */
    public function chance(int $chances, int $of = 100): bool
    {
        return random_int(1, $of) <= $chances;
    }

    public function getMessage(): ?MessageDataProxy
    {
        return $this->update->message;
    }

    public function isCommand(string $string): bool
    {
        return $this->matchFullKeyword([
                '^/' . $string . '$',
                '^/' . $string . '@' . $this->botService->entity->getName() . '$',
            ]) !== null;
    }

    public function setParseMode(string $parseMode): static
    {
        $this->parseMode = $parseMode;

        return $this;
    }

    public function setChat(Chat $chat): static
    {
        $this->chat = $chat;

        return $this;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function setUserInChat(UserInChat $userInChat): static
    {
        $this->userInChat = $userInChat;

        return $this;
    }
}