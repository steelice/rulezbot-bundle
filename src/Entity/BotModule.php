<?php

namespace Rulezdev\RulezbotBundle\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[ORM\Index(columns: ['priority'])]
class BotModule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id;

    #[Gedmo\Timestampable(on: "create")]
    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $alias;

    #[ORM\Column(type: 'string', unique: true)]
    private string $className;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isEnabledGlobal = true;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isEnabledByDefault = true;

    #[ORM\Column(type: 'string', length: 255)]
    private string $cachedName;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $cachedDescription = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(type: "integer", nullable: false, options: ['default' => 1000])]
    private int $priority = 1000;

    public function __construct($alias, $className)
    {
        $this->alias = $alias;
        $this->className = $className;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @param string $alias
     * @return BotModule
     */
    public function setAlias(string $alias): BotModule
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @param string $className
     * @return BotModule
     */
    public function setClassName(string $className): BotModule
    {
        $this->className = $className;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabledGlobal(): bool
    {
        return $this->isEnabledGlobal;
    }

    /**
     * @param bool $isEnabledGlobal
     * @return BotModule
     */
    public function setIsEnabledGlobal(bool $isEnabledGlobal): BotModule
    {
        $this->isEnabledGlobal = $isEnabledGlobal;

        return $this;
    }

    public function getCachedName(): ?string
    {
        return $this->cachedName;
    }

    public function setCachedName(string $cachedName): self
    {
        $this->cachedName = $cachedName;

        return $this;
    }

    public function getCachedDescription(): ?string
    {
        return $this->cachedDescription;
    }

    public function setCachedDescription(?string $cachedDescription): self
    {
        $this->cachedDescription = $cachedDescription;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function setPriority(int $priority): BotModule
    {
        $this->priority = $priority;

        return $this;
    }
}