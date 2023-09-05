<?php

namespace Rulezdev\RulezbotBundle\Entity;

use Rulezdev\RulezbotBundle\Repository\BotInChatRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Rulezdev\RulezbotBundle\Entity\Bot;
use Rulezdev\RulezbotBundle\Entity\Chat;

#[ORM\Entity(repositoryClass: BotInChatRepository::class)]
#[ORM\Table]
#[ORM\UniqueConstraint(name: 'bot_id', columns: ['bot_id', 'chat_id'])]
class BotInChat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Bot::class)]
    private Bot $bot;

    #[ORM\ManyToOne(targetEntity: Chat::class)]
    private Chat $chat;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $is_active = true;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private DateTimeImmutable $last_activity;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $deactivatedAt = null;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true, 'default' => 0])]
    private int $total_requests = 0;

    public function __construct(Bot $bot, Chat $chat)
    {
        $this->last_activity = new DateTimeImmutable();
        $this->bot = $bot;
        $this->chat = $chat;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBot(): Bot
    {
        return $this->bot;
    }

    public function getChat(): Chat
    {
        return $this->chat;
    }

    public function getIsActive(): bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): self
    {
        $this->is_active = $is_active;
        if (!$is_active) {
            $this->deactivatedAt = new DateTimeImmutable();
        }

        return $this;
    }

    public function getLastActivity(): ?DateTimeInterface
    {
        return $this->last_activity;
    }

    public function setLastActivity(DateTimeInterface $last_activity): self
    {
        $this->last_activity = $last_activity;

        return $this;
    }

    public function getTotalRequests(): int
    {
        return $this->total_requests;
    }

    public function setTotalRequests(int $total_requests): self
    {
        $this->total_requests = $total_requests;

        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getDeactivatedAt(): ?DateTimeImmutable
    {
        return $this->deactivatedAt;
    }
}
