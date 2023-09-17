<?php

namespace Rulezdev\RulezbotBundle\Service;

use Rulezdev\RulezbotBundle\Entity\BotModule;
use Rulezdev\RulezbotBundle\Entity\UserInChat;
use Rulezdev\RulezbotBundle\Repository\UserInChatRepository;

class WorkflowService
{
    protected UserInChat $uic;
    public function __construct(
        private readonly UserInChatRepository $userInChatRepository,
    )
    {
    }

    public function init(UserInChat $uic): self
    {
        $this->uic = $uic;

        return $this;
    }

    public function setWorkflow(?BotModule $module, ?string $stage = null, ?array $data = null): self
    {
        $this->uic->setWorkflowModule($module);
        $this->uic->setWorkflowStage($stage);
        if ($data !== null) {
            $this->uic->setWorkflowData($data);
        }
        $this->userInChatRepository->save($this->uic, true);

        return $this;
    }

    public function setStage(?string $stage): self
    {
        $this->uic->setWorkflowStage($stage);
        $this->userInChatRepository->save($this->uic, true);

        return $this;
    }

    public function getStage(): ?string
    {
        return $this->uic->getWorkflowStage();
    }

    public function setData(?array $data): self
    {
        $this->uic->setWorkflowData($data);
        $this->userInChatRepository->save($this->uic, true);

        return $this;
    }

    /**
     * Clear all workflow data
     *
     * @return $this
     */
    public function clear(): self
    {
        $this->uic
            ->setWorkflow(null)
            ->setWorkflowStage(null)
            ->setWorkflowData(null)
            ->setWorkflowModule(null)
        ;
        $this->userInChatRepository->save($this->uic, true);

        return $this;
    }

}