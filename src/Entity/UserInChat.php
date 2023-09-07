<?php

namespace Rulezdev\RulezbotBundle\Entity;

use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Rulezdev\RulezbotBundle\Repository\UserInChatRepository;

#[ORM\Entity(repositoryClass: UserInChatRepository::class)]
#[ORM\Table]
#[ORM\UniqueConstraint(name: 'bot_id', columns: ['user_id', 'chat_id'])]
#[ORM\Index(columns: ['chat_id', 'last_seen_at'], name: 'chat_id')]
#[ORM\Index(columns: ['chat_id', 'msg_role'], name: 'chat_users')]
class UserInChat
{
    public const ROLE_ABSENT = -1;
    public const ROLE_USER = 0;
    public const ROLE_ADMIN = 1;
    public const ROLE_OWNER = 2;
    public const ROLE_NAMES = [
        self::ROLE_USER   => 'Юзер',
        self::ROLE_ADMIN  => 'Админ',
        self::ROLE_OWNER  => 'Владелец',
        self::ROLE_ABSENT => 'Удалён',
    ];

    public const BANNED_REASON_UNKNOWN = 1;
    public const BANNED_REASON_HATE = 2;
    public const BANNED_REASON_SEX = 3;
    public const BANNED_REASON_VIOLENCE = 4;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Chat::class, cascade: ['persist'], inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Chat $chat;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private DateTime $lastSeenAt;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private DateTime $firstSeenAt;

    #[ORM\Column(type: 'float', options: ['default' => 1])]
    private float $totalRating = 1;

    #[ORM\Column(type: 'smallint', options: ['default' => 0])]
    private int $msgRole = self::ROLE_USER;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $karlRole = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $how2call;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $bannedTill = null;

    #[ORM\Column(nullable: true)]
    private ?int $bannedReason = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $workflow = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $workflowStage = null;

    #[ORM\Column(nullable: true)]
    private ?array $workflowData = [];

    #[ORM\ManyToOne]
    private ?BotModule $workflowModule = null;

    public function __construct(Chat $chat, User $user)
    {
        $this->how2call = $user->getHow2call();
        $this->lastSeenAt = new DateTime();
        $this->firstSeenAt = new DateTime();
        $this->chat = $chat;
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getChat(): ?Chat
    {
        return $this->chat;
    }

    /**
     * @param mixed $chat
     * @return UserInChat
     */
    public function setChat(Chat $chat): static
    {
        $this->chat = $chat;

        return $this;
    }

    public function getLastSeenAt(): DateTime
    {
        return $this->lastSeenAt;
    }

    public function setLastSeenAt(DateTime $lastSeenAt): static
    {
        $this->lastSeenAt = $lastSeenAt;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFirstSeenAt(): DateTime
    {
        return $this->firstSeenAt;
    }

    /**
     * @return mixed
     */
    public function getTotalRating(): float
    {
        return $this->totalRating;
    }

    /**
     * @param mixed $totalRating
     * @return UserInChat
     */
    public function setTotalRating(float $totalRating): static
    {
        $this->totalRating = $totalRating;

        return $this;
    }

    public function getHow2call(): ?string
    {
        return $this->how2call ?: $this->user->getHow2call();
    }

    /**
     * @param string $how2call
     * @return UserInChat
     */
    public function setHow2call(string $how2call): UserInChat
    {
        $this->how2call = $how2call;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getMsgRole(): int
    {
        return $this->msgRole;
    }

    /**
     * @param int $msgRole
     */
    public function setMsgRole(int $msgRole): void
    {
        $this->msgRole = $msgRole;
    }

    public function getKarlRole(): ?int
    {
        return $this->karlRole;
    }

    public function setKarlRole(?int $karlRole): self
    {
        $this->karlRole = $karlRole;

        return $this;
    }

    public function getLinkableName(): string
    {
        return sprintf("[%s](tg://user?id=%s)",
            $this->how2call ?: $this->user->getHow2call(),
            $this->user->getMsgId()
        );
    }

    public function getRoleName(): string
    {
        return self::ROLE_NAMES[$this->msgRole] ?? '-';
    }

    public function getKarlRoleName(): string
    {
        return self::ROLE_NAMES[$this->karlRole] ?? '-';
    }

    public function getBannedTill(): ?DateTimeImmutable
    {
        return $this->bannedTill;
    }

    public function setBannedTill(?DateTimeImmutable $bannedTill): self
    {
        $this->bannedTill = $bannedTill;

        return $this;
    }

    public function getBannedReason(): ?int
    {
        return $this->bannedReason;
    }

    public function setBannedReason(?int $bannedReason): self
    {
        $this->bannedReason = $bannedReason;

        return $this;
    }

    public function getWorkflow(): ?string
    {
        return $this->workflow;
    }

    public function setWorkflow(?string $workflow): self
    {
        $this->workflow = $workflow;

        return $this;
    }

    public function getWorkflowStage(): ?string
    {
        return $this->workflowStage;
    }

    public function setWorkflowStage(?string $workflowStage): self
    {
        $this->workflowStage = $workflowStage;

        return $this;
    }

    public function getWorkflowData(): array
    {
        return $this->workflowData;
    }

    public function setWorkflowData(?array $workflowData): self
    {
        $this->workflowData = $workflowData;

        return $this;
    }

    public function getWorkflowDataItem(string $key, mixed $default = null): mixed
    {
        return $this->workflowData[$key] ?? $default;
    }

    public function setWorkflowDataItem(string $key, mixed $value): self
    {
        if (!is_array($this->workflowData)) {
            $this->workflowData = [];
        }
        $this->workflowData[$key] = $value;

        return $this;
    }

    public function getWorkflowModule(): ?BotModule
    {
        return $this->workflowModule;
    }

    public function setWorkflowModule(?BotModule $workflowModule): static
    {
        $this->workflowModule = $workflowModule;

        return $this;
    }

}