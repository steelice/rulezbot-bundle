<?php

namespace Rulezdev\RulezbotBundle\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table()]
#[ORM\Index(columns: ['internal_id'], name: 'internal_id')]
#[ORM\Index(columns: ['last_seen'], name: 'last_seen')]
#[ORM\UniqueConstraint(name: 'tg_id', columns: ['msg_id'])]
class User implements HasFileEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(type: 'bigint', unique: true)]
    private int $msgId;

    #[ORM\Column(name: 'msg_username', type: 'string', length: 100, nullable: true, options: ['default' => ''])]
    private ?string $username = '';

    #[ORM\Column(name: 'msg_first_name', type: 'string', length: 100, nullable: true, options: ['default' => ''])]
    private string $firstName = '';

    #[ORM\Column(name: 'msg_last_name', type: 'string', length: 100, nullable: true, options: ['default' => ''])]
    private string $lastName = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => ''])]
    private string $how2call = '';

    #[ORM\Column(type: 'integer', options: ['default' => 2])]
    private int $timezone = 2;

    #[ORM\Column(type: 'integer', nullable: true)]
    private mixed $internalId;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $dateAdd;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $isSendQ;

    #[ORM\Column(type: 'array', nullable: true)]
    private ?array $customSettings = null;

    #[ORM\Column(type: 'string', length: 250, nullable: true)]
    private mixed $userpic;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private mixed $lastUserpicUpdate;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private $updatedAt;

    #[ORM\Column(name: 'sex', type: 'string', length: 1, options: ['default' => 'm'])]
    private string $gender = 'm';

    #[ORM\Column(type: 'string', length: 2, options: ['default' => 'ru'])]
    private string $lang = 'ru';

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $geoLat;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $geoLng;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $lastSeen;

    public function __construct()
    {
        $this->geos = new ArrayCollection();
        $this->youtubeMP3s = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    public function getMsgId(): int
    {
        return $this->msgId;
    }

    /**
     * @param mixed $msgId
     * @return User
     */
    public function setMsgId($msgId)
    {
        $this->msgId = $msgId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param mixed $firstName
     * @return User
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param mixed $lastName
     * @return User
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getHow2call()
    {
        return $this->how2call;
    }

    /**
     * @param mixed $how2call
     * @return User
     */
    public function setHow2call($how2call)
    {
        $this->how2call = $how2call;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @param mixed $timezone
     * @return User
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getInternalId()
    {
        return $this->internalId;
    }

    /**
     * @param mixed $internalId
     * @return User
     */
    public function setInternalId($internalId)
    {
        $this->internalId = $internalId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Возвращает точно либо m, либо f
     * @return string
     */
    public function getGenderSymbol(): string
    {
        return $this->isFemale() ? 'f' : 'm';
    }

    /**
     * @param mixed $gender
     * @return User
     */
    public function setGender(string $gender)
    {
        if (!in_array($gender, ['m', 'f'])) {
            $gender = 'm';
        }

        $this->gender = $gender;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLang(): ?string
    {
        return $this->lang;
    }

    /**
     * @param mixed $lang
     * @return User
     */
    public function setLang(string $lang)
    {
        $this->lang = $lang;

        return $this;
    }

    public function getGeoLat(): ?float
    {
        return $this->geoLat;
    }

    public function setGeoLat(?float $geoLat): self
    {
        $this->geoLat = $geoLat;

        return $this;
    }

    public function getGeoLng(): ?float
    {
        return $this->geoLng;
    }

    public function setGeoLng(?float $geoLng): self
    {
        $this->geoLng = $geoLng;

        return $this;
    }

    public function getLastSeen(): ?DateTimeInterface
    {
        return $this->lastSeen;
    }

    public function setLastSeen(DateTimeInterface $lastSeen): self
    {
        $this->lastSeen = $lastSeen;

        return $this;
    }

    public function getDirUDID(): string
    {
        return $this->getMsgId();
    }

    public static function getEntityType(): string
    {
        return 'user';
    }

    public function getUserpic(): ?string
    {
        return $this->userpic;
    }

    /**
     * @param string $userpic
     */
    public function setUserpic($userpic): self
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
     * @return self
     */
    public function setLastUserpicUpdate(DateTimeImmutable $lastUserpicUpdate): self
    {
        $this->lastUserpicUpdate = $lastUserpicUpdate;

        return $this;
    }

    public function isFemale(): bool
    {
        return $this->gender === 'f';
    }
}