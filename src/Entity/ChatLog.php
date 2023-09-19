<?php

namespace Rulezdev\RulezbotBundle\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Rulezdev\RulezbotBundle\Repository\ChatLogRepository;

#[ORM\Entity(repositoryClass: ChatLogRepository::class)]
#[ORM\Table]
#[ORM\Index(columns: ['created_at'], name: 'idx_chat_logs_active_at')]
#[ORM\Index(columns: ['chat_id', 'created_at'], name: 'idx_chat_logs_chat_active_at')]
#[ORM\Index(columns: ['user_id', 'created_at'], name: 'idx_user_logs_chat_active_at')]
class ChatLog
{
    public const TYPE_TEXT = 'text';
    public const TYPE_PHOTO = 'photo';
    public const TYPE_STICKER = 'sticker';
    public const TYPE_VIDEO = 'video';
    public const TYPE_ANIMATION = 'gif';
    public const TYPE_AUDIO = 'audio';
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_LOCATION = 'location';
    public const TYPE_POLL = 'poll';
    public const TYPE_UNKNOWN = 'unknown';
    public const TYPE_VOICE = 'voice';
    public const TYPE_NEW_MEMBER = 'newmember';
    public const TYPE_LEFT_MEMBER = 'leftmember';
    public const TYPE_CALLBACK = 'callback';
    public const TYPE_EDITED_TEXT = 'edit_text';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Bot $bot;

    #[ORM\ManyToOne(targetEntity: Chat::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Chat $chat;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?User $toUser = null;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'text'])]
    private string $type;

    #[ORM\Column(type: 'text')]
    private string $text;

    #[ORM\Column(type: 'boolean')]
    private bool $is_replied = false;

    #[ORM\Column(type: 'bigint')]
    private int $message_id;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $toMessageId = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isToMe = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(Bot $bot, Chat $chat, User $msgUser, string $type, string $text)
    {
        $this->chat = $chat;
        $this->bot = $bot;
        $this->user = $msgUser;
        $this->createdAt = new DateTimeImmutable();
        $this->type = $type;
        $this->text = $text;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChat(): Chat
    {
        return $this->chat;
    }

    public function setChat(Chat $chat): self
    {
        $this->chat = $chat;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getIsReplied(): bool
    {
        return $this->is_replied;
    }

    public function setIsReplied(bool $is_replied): self
    {
        $this->is_replied = $is_replied;

        return $this;
    }

    public function getMessageId(): ?int
    {
        return $this->message_id;
    }

    public function setMessageId(int $message_id): self
    {
        $this->message_id = $message_id;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getBot(): Bot
    {
        return $this->bot;
    }

    public function setBot(Bot $Bot): self
    {
        $this->bot = $Bot;

        return $this;
    }

    /**
     * @param bool $isToMe
     * @return ChatLog
     */
    public function setIsToMe(bool $isToMe): self
    {
        $this->isToMe = $isToMe;

        return $this;
    }
}
