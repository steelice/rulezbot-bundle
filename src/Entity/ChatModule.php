<?php

namespace Rulezdev\RulezbotBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'Rulezdev\RulezbotBundle\Repository\ChatModuleRepository')]
#[ORM\Table]
class ChatModule
{
    /**
     * @var Chat
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: 'Rulezdev\RulezbotBundle\Entity\Chat')]
    #[ORM\JoinColumn(nullable: false)]
    private Chat $chat;
    /**
     * @var BotModule
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: 'Rulezdev\RulezbotBundle\Entity\BotModule')]
    #[ORM\JoinColumn(nullable: false)]
    private BotModule $module;

    #[ORM\Column(type: 'boolean')]
    private bool $isEnabled = true;

    #[ORM\Column(type: 'integer', nullable: true)]
    private int $position = 0;

    #[ORM\Column(type: 'json', nullable: true)]
    private array $settings = [];

    public function __construct(Chat $chat, BotModule $module)
    {
        $this->chat = $chat;
        $this->module = $module;
    }

    public function getChat(): Chat
    {
        return $this->chat;
    }

    public function setChat(?Chat $chat): self
    {
        $this->chat = $chat;

        return $this;
    }

    public function getModule(): BotModule
    {
        return $this->module;
    }

    public function setModule(?BotModule $module): self
    {
        $this->module = $module;

        return $this;
    }

    public function getIsEnabled(): ?bool
    {
        return $this->isEnabled && $this->module->isEnabledGlobal();
    }

    public function setIsEnabled(bool $isEnabled): self
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getSettings(): ?array
    {
        $class = $this->module->getClassName();

        return array_merge($class::getDefaultSettings(), $this->settings);
    }

    public function setSettings(?array $settings): self
    {
        $this->settings = $settings;

        return $this;
    }
}
