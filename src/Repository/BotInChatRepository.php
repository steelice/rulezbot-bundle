<?php

namespace Rulezdev\RulezbotBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Rulezdev\RulezbotBundle\Entity\Bot;
use Rulezdev\RulezbotBundle\Entity\BotInChat;
use Rulezdev\RulezbotBundle\Entity\Chat;

/**
 * @method BotInChat|null find($id, $lockMode = null, $lockVersion = null)
 * @method BotInChat|null findOneBy(array $criteria, array $orderBy = null)
 * @method BotInChat[]    findAll()
 * @method BotInChat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BotInChatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BotInChat::class);
    }

    public function getOrCreate(Bot $bot, Chat $chat): BotInChat
    {
        $botInChat = $this->findOneBy([
            'chat' => $chat,
            'bot'  => $bot
        ]);

        if ($botInChat) {
            return $botInChat;
        }

        $botInChat = new BotInChat($bot, $chat);
        $this->save($botInChat);

        return $botInChat;
    }

    public function save(BotInChat $botInChat)
    {
        $this->_em->persist($botInChat);
        $this->_em->flush();
    }
}
