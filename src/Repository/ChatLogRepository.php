<?php

namespace Rulezdev\RulezbotBundle\Repository;

use Rulezdev\RulezbotBundle\Model\Response\ThemedLogLine;
use Rulezdev\RulezbotBundle\TgDataProxy\MessageDataProxy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Rulezdev\RulezbotBundle\Entity\Bot;
use Rulezdev\RulezbotBundle\Entity\Chat;
use Rulezdev\RulezbotBundle\Entity\ChatLog;
use Rulezdev\RulezbotBundle\Entity\User;

/**
 * @method ChatLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChatLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChatLog[]    findAll()
 * @method ChatLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChatLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatLog::class);
    }

    /**
     * Возвращает логи для чата
     *
     * @param Chat $chat
     * @param int  $maxResults
     * @return ChatLog[]
     */
    public function findByChat(Chat $chat, int $maxResults = 300): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.user', 'u')
            ->addSelect('u')
            ->andWhere('c.chat = :chat')
            ->orderBy('c.id', 'DESC')
            ->setParameters([
                'chat' => $chat
            ])
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }

    public function logMessage(Bot $bot, Chat $chat, User $user, MessageDataProxy $message): ChatLog
    {
        $type = $message->getType();
        $text = $message->getText();

        $this->getEntityManager()->beginTransaction();
        $messageLog = new ChatLog($bot, $chat, $user, $type, $text);
        $messageLog->setMessageId($message->getId());

        if ($chat->getIsPrivate()) {
            $messageLog->setIsToMe(true);
        } elseif ($message->replyToMessage !== null && $bot->getMsgId() === $message->replyToMessage->from->getId()) {
            $messageLog->setIsToMe(true);
        }

        $this->_em->persist($messageLog);
        $this->_em->flush();
        $this->getEntityManager()->commit();

        return $messageLog;
    }

    /**
     * @param int $maxResults
     * @return ThemedLogLine[]
     */
    public function findAboutKarl(int $maxResults = 50, int $maxNestedResults = 5): array
    {
        /** @var ChatLog[] $mainPhrases */
        $mainPhrases = $this->createQueryBuilder('c')
            ->join('c.chat', 'chat')
            ->join('c.user', 'user')
            ->addSelect('chat')
            ->addSelect('user')
            ->orderBy('c.id', 'DESC')
            ->where('c.text LIKE :filter')
            ->setParameter('filter', '%карл%')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();

        return array_map(fn(ChatLog $mainPhrase): ThemedLogLine => new ThemedLogLine(
            $mainPhrase,
            $this->getNestedForLogLine($mainPhrase, $maxNestedResults)
        ), $mainPhrases);
    }

    private function getNestedForLogLine(ChatLog $mainPhrase, int $maxResults = 10)
    {
        return $this->createQueryBuilder('c')
            ->join('c.user', 'user')
            ->addSelect('user')
            ->orderBy('c.id', 'ASC')
            ->where('c.chat = :chat AND c.id > :id')
            ->setParameter('chat', $mainPhrase->getChat())
            ->setParameter('id', $mainPhrase->getId())
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }
}
