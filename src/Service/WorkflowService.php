<?php

namespace Rulezdev\RulezbotBundle\Service;

use Rulezdev\RulezbotBundle\Entity\UserInChat;
use Rulezdev\RulezbotBundle\Repository\UserInChatRepository;

class WorkflowService
{
    public function __construct(
        private readonly UserInChatRepository $userInChatRepository,
    )
    {
    }

    public function setWorkflow(UserInChat $uic, string $workflow, ?string $stage = null, ?array $data = null): self
    {
        $uic->setWorkflow($workflow);
        $uic->setWorkflowStage($stage);
        if ($data !== null) {
            $uic->setWorkflowData($data);
        }
        $this->userInChatRepository->save($uic, true);

        return $this;
    }

    public function setStage(UserInChat $uic, ?string $stage): self
    {
        $uic->setWorkflowStage($stage);
        $this->userInChatRepository->save($uic, true);

        return $this;
    }

    public function setData(UserInChat $uic, ?array $data): self
    {
        $uic->setWorkflowData($data);
        $this->userInChatRepository->save($uic, true);

        return $this;
    }
}