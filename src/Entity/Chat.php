<?php

namespace Rulezdev\RulezbotBundle\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Rulezdev\RulezbotBundle\Entity\UserInChat;

#[ORM\Entity]
#[ORM\Table]
#[ORM\UniqueConstraint(name: 'msg_network_id', columns: ['network_id', 'msg_chat_id'])]
#[ORM\Index(columns: ['msg_chat_id'], name: 'msg_chat_id')]
class Chat implements HasFileEntityInterface
{
    public const BIG_USERPIC_PREFIX = 'big-';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $networkId = 1;

    #[ORM\Column(type: 'bigint')]
    private int $msgChatId;

    #[ORM\Column(type: 'string', length: 255, options: ['default' => ''])]
    private string $name;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isPrivate;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'], columnDefinition: 'DATETIME on update CURRENT_TIMESTAMP')]
    private DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $userpic = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private DateTimeImmutable $lastUserpicUpdate;

    #[ORM\OneToMany(mappedBy: 'chat', targetEntity: UserInChat::class)]
    private $users;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $msgDescription = null;

    #[ORM\Column(type: 'bigint', options: ['default' => 0, 'unsigned' => true])]
    private int $total_content_length = 0;

    #[ORM\Column(type: "string", length: 20, nullable: false, options: ['default' => ''])]
    private ?string $type;

    #[ORM\Column(nullable: true, options: ['default' => null])]
    private ?DateTimeImmutable $premiumTill = null;

    public function __construct(int $msgChatId, string $type, int $networkId)
    {
        $this->setMsgChatId($msgChatId);
        $this->type = $type;
        $this->networkId = $networkId;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function __toString()
    {
        return $this->name ?: '#' . $this->id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return Chat
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getNetworkId()
    {
        return $this->networkId;
    }

    /**
     * @param mixed $networkId
     * @return Chat
     */
    public function setNetworkId($networkId)
    {
        $this->networkId = $networkId;

        return $this;
    }

    public function getMsgChatId(): int
    {
        return $this->msgChatId;
    }

    /**
     * @param mixed $msgChatId
     * @return Chat
     */
    public function setMsgChatId(int $msgChatId): static
    {
        $this->msgChatId = $msgChatId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    public function getNameWithId()
    {
        return sprintf('%s (%s)', $this->name, $this->msgChatId);
    }

    /**
     * @param mixed $name
     * @return Chat
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsPrivate()
    {
        return $this->isPrivate;
    }

    /**
     * @param mixed $isPrivate
     * @return Chat
     */
    public function setIsPrivate($isPrivate)
    {
        $this->isPrivate = $isPrivate;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     * @return Chat
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUserpic()
    {
        return $this->userpic;
    }

    /**
     * @param mixed $userpic
     * @return Chat
     */
    public function setUserpic($userpic)
    {
        $this->userpic = $userpic;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastUserpicUpdate()
    {
        return $this->lastUserpicUpdate;
    }

    /**
     * @param mixed $lastUserpicUpdate
     * @return Chat
     */
    public function setLastUserpicUpdate(DateTimeImmutable $lastUserpicUpdate)
    {
        $this->lastUserpicUpdate = $lastUserpicUpdate;

        return $this;
    }

    /**
     * @return UserInChat[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    public function getDirUDID(): string
    {
        return $this->getMsgChatId();
    }

    public static function getEntityType(): string
    {
        return 'chat';
    }

    public function getMsgDescription(): ?string
    {
        return $this->msgDescription;
    }

    public function setMsgDescription(?string $msgDescription): self
    {
        $this->msgDescription = $msgDescription;

        return $this;
    }

    public function getTotalContentLength(): ?string
    {
        return $this->total_content_length;
    }

    public function setTotalContentLength(string $total_content_length): self
    {
        $this->total_content_length = $total_content_length;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getPremiumTill(): ?DateTimeImmutable
    {
        return $this->premiumTill;
    }

    public function setPremiumTill(?DateTimeImmutable $premiumTill): self
    {
        $this->premiumTill = $premiumTill;

        return $this;
    }

    public function isPremium(): bool
    {
        if (!$this->premiumTill) {
            return false;
        }

        return $this->premiumTill >= (new DateTimeImmutable());
    }

    public function getPublicAuthCode(): string
    {
        return sha1(implode('-', [
            $this->getId(),
            $this->getMsgChatId(),
            $this->getCreatedAt()->format('Y-m-d H:i:s'),
        ]));
    }
}