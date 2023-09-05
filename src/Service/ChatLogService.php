<?php

namespace Rulezdev\RulezbotBundle\Service;

use Rulezdev\RulezbotBundle\TgDataProxy\UpdateProxy;
use Doctrine\ORM\EntityManagerInterface;
use Rulezdev\RulezbotBundle\Entity\ChatLog;
use Rulezdev\RulezbotBundle\Entity\User;
use Rulezdev\RulezbotBundle\Entity\UserInChat;
use Rulezdev\RulezbotBundle\Repository\ChatLogRepository;
use Rulezdev\RulezbotBundle\Repository\UserInChatRepository;
use Rulezdev\RulezbotBundle\Repository\UserRepository;

class ChatLogService
{
    private static array $stopWords = [];

    public function __construct(
        private readonly UserInChatRepository   $userInChatRepository,
        private readonly ChatLogRepository      $chatLogs,
        private readonly BotService             $botService,
        private readonly UserRepository         $msgUserRepository,
        private readonly EntityManagerInterface $em,
    )
    {
    }

    public function logMessage(UserInChat $uic, UpdateProxy $update): ChatLog
    {
        $user = $uic->getUser();
        $chat = $uic->getChat();

        $log = $this->chatLogs->logMessage($this->botService->getEntity(), $chat, $user, $update->message);

        if ($update->getType() === ChatLog::TYPE_LEFT_MEMBER) {
            $leftUser = $this->msgUserRepository->getOrCreateUser($update->message->getOriginalMessage()->getLeftChatMember());
            $leftUserInChat = $this->userInChatRepository->getOrCreate($leftUser, $chat, false);
            $leftUserInChat->setLastSeenAt(new \DateTime());
            $leftUserInChat->setMsgRole(UserInChat::ROLE_ABSENT);
        } elseif ($update->getType() === ChatLog::TYPE_NEW_MEMBER) {
            /** @var User $newChatMember */
            foreach ($update->message->getOriginalMessage()->getNewChatMembers() as $newChatMember) {
                $newUser = $this->msgUserRepository->getOrCreateUser($newChatMember);
                $newUserInChat = $this->userInChatRepository->getOrCreate($newUser, $chat, false);
                $newUserInChat->setMsgRole(UserInChat::ROLE_USER);
            }
        }
        $uic->setLastSeenAt(new \DateTime());
        $user->setLastSeen(new \DateTimeImmutable());

        $this->em->flush();

        return $log;
    }
}