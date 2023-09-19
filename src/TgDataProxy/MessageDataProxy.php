<?php

namespace Rulezdev\RulezbotBundle\TgDataProxy;

use Rulezdev\RulezbotBundle\Helper\TextFormat;
use Rulezdev\RulezbotBundle\Entity\ChatLog;
use TelegramBot\Api\Types\Location;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\PhotoSize;
use TelegramBot\Api\Types\User;

class MessageDataProxy
{
    public FromDataProxy $from;
    public ChatDataProxy $chat;
    public ?MessageDataProxy $replyToMessage = null;

    public function __construct(
        protected Message $message,
        public readonly bool $isEdited = false,
    )
    {
        $this->from = new FromDataProxy($this->message->getFrom());
        $this->chat = new ChatDataProxy($this->message->getChat());
        if ($this->message->getReplyToMessage()) {
            $this->replyToMessage = new MessageDataProxy($this->message->getReplyToMessage());
        }
    }

    /**
     * Возвращает текст сообщения с учётом его типа
     */
    public function getText(): string
    {
        $text = match ($this->getType()) {
            ChatLog::TYPE_STICKER => $this->message->getSticker()->toJson(),
            ChatLog::TYPE_VIDEO => $this->message->getVideo()->toJson(),
            ChatLog::TYPE_AUDIO => $this->message->getAudio()->toJson(),
            ChatLog::TYPE_POLL => $this->message->getPoll()->toJson(),
            ChatLog::TYPE_ANIMATION => $this->message->getAnimation()->toJson(),
            ChatLog::TYPE_LOCATION => $this->message->getLocation()->toJson(),
            ChatLog::TYPE_DOCUMENT => $this->message->getDocument()->toJson(),
            ChatLog::TYPE_PHOTO => json_encode(array_map(static fn(PhotoSize $photo): array => [
                'fileId' => $photo->getFileId(),
                'height' => $photo->getHeight(),
                'width'  => $photo->getWidth(),
            ], $this->message->getPhoto()), JSON_THROW_ON_ERROR),
            ChatLog::TYPE_TEXT, ChatLog::TYPE_EDITED_TEXT => $this->message->getText(),
            ChatLog::TYPE_VOICE => $this->message->getVoice()->toJson(),
            ChatLog::TYPE_NEW_MEMBER => json_encode(array_map(static fn(User $user): array => [
                'username'  => $user->getUsername(),
                'id'        => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName'  => $user->getLastName(),
            ], $this->message->getNewChatMembers()), JSON_THROW_ON_ERROR),
            ChatLog::TYPE_LEFT_MEMBER => $this->message->getLeftChatMember()->toJson(),

            default => $this->message->toJson(),
        };

        return $text ?: '';
    }

    public function getId(): int
    {
        return $this->message->getMessageId();
    }

    public function getOriginalMessage(): Message
    {
        return $this->message;
    }

    public function getType(): string
    {
        return match (true) {
            $this->isEdited && ($this->message->getText() !== null) => ChatLog::TYPE_EDITED_TEXT,
            $this->message->getSticker() !== null => ChatLog::TYPE_STICKER,
            $this->message->getVideo() !== null => ChatLog::TYPE_VIDEO,
            $this->message->getPhoto() !== null => ChatLog::TYPE_PHOTO,
            $this->message->getAnimation() !== null => ChatLog::TYPE_ANIMATION,
            $this->message->getAudio() !== null => ChatLog::TYPE_AUDIO,
            $this->message->getDocument() !== null => ChatLog::TYPE_DOCUMENT,
            $this->message->getLocation() !== null => ChatLog::TYPE_LOCATION,
            $this->message->getPoll() !== null => ChatLog::TYPE_POLL,
            $this->message->getText() !== null => ChatLog::TYPE_TEXT,
            $this->message->getVoice() !== null => ChatLog::TYPE_VOICE,
            $this->message->getNewChatMembers() !== null => ChatLog::TYPE_NEW_MEMBER,
            $this->message->getLeftChatMember() !== null => ChatLog::TYPE_LEFT_MEMBER,

            default => ChatLog::TYPE_UNKNOWN
        };
    }

    public function isSmallerThan(int $maxLength): bool
    {
        return $this->getType() === ChatLog::TYPE_TEXT && mb_strlen($this->getNormalizedText()) < $maxLength;
    }

    /**
     * Проверяет, что длинна текста находится в заданных рамках
     */
    public function lengthBetween(int $minWidth, int $maxWidth, bool $onlyText = false): bool
    {
        if ($this->getType() !== ChatLog::TYPE_TEXT) {
            return false;
        }

        $length = mb_strlen($onlyText ? $this->getOnlyText() : $this->getNormalizedText());

        return $length >= $minWidth && $length <= $maxWidth;
    }

    public function getNormalizedText(bool $lower = true): string
    {
        return TextFormat::normalizeText($this->getText(), $lower);
    }

    public function getOnlyText(bool $lower = true): string
    {
        return TextFormat::onlyText($this->getText(), $lower);
    }

    /**
     * Если текущее сообщение - фото, то вернёт массив с наибольшим фото. Иначе вернёт null
     * @return array|null
     */
    public function getBiggestPhoto(): ?PhotoSize
    {
        if (!$photos = $this->message->getPhoto()) {
            return null;
        }

        $photo = end($photos);

        return $photo ?: null;
    }

    public function getCaption(): ?string
    {
        return $this->message->getCaption() ?: null;
    }

    public function getVideoFileId()
    {
        return $this->message->getVideo()->getFileId();
    }

    public function getAnimationFileId()
    {
        return $this->message->getAnimation()->getFileId();
    }

    public function getLocation(): Location
    {
        return $this->message->getLocation();
    }

    public function isLocation(): bool
    {
        return $this->message->getLocation() instanceof Location;
    }

}