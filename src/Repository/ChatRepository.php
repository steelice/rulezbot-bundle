<?php

namespace Rulezdev\RulezbotBundle\Repository;

use Rulezdev\RulezbotBundle\Service\BotService;
use Rulezdev\RulezbotBundle\TgDataProxy\MessageDataProxy;
use Rulezdev\RulezbotBundle\TgDataProxy\UpdateProxy;
use Carbon\Carbon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Rulezdev\RulezbotBundle\Entity\Bot;
use Rulezdev\RulezbotBundle\Entity\BotInChat;
use Rulezdev\RulezbotBundle\Entity\Chat;

/**
 * @method Chat|null find($id, $lockMode = null, $lockVersion = null)
 * @method Chat|null findOneBy(array $criteria, array $orderBy = null)
 * @method Chat[]    findAll()
 * @method Chat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChatRepository extends ServiceEntityRepository
{

    public function __construct(
        ManagerRegistry             $registry,
        private readonly BotService $botService
    )
    {
        parent::__construct($registry, Chat::class);
    }



    public function getChatByMessage(MessageDataProxy $message): Chat
    {
        if ($chat = $this->findOneBy(['msgChatId' => $message->chat->getId()])) {
            return $chat;
        }

        $chat = new Chat($message->chat->getId(), $message->chat->getType(), $this->botService->entity->getNetworkId());
        $chat
            ->setName($message->chat->getName())
            ->setIsPrivate($message->chat->isPrivate());

        $this->_em->persist($chat);

        $botInChat = new BotInChat($this->botService->getEntity(), $chat);
        $this->_em->persist($botInChat);

        $this->_em->flush();

        return $chat;
    }

    /**
     * Возвращает все чаты для бота
     *
     * @param Bot $bot
     * @param string|null $withModuleEnabled
     * @return array|null
     */
    public function getAllForBot(Bot $bot, string $withModuleEnabled = null): ?array
    {
        $qb = $this->getQbForBot($bot);

        if ($withModuleEnabled) {
            $qb
                ->join('c.chatModules', 'cm')
                ->join('cm.module', 'md')
                ->andWhere('md.alias = :alias')
                ->setParameter('alias', $withModuleEnabled);
        }

        return $qb->getQuery()->getResult();
    }

    public function updateChatStats(Chat $chat, UpdateProxy $updateProxy): void
    {
        $this->getEntityManager()->createQuery('
            UPDATE Rulezdev\RulezbotBundle\Entity\Chat c 
            SET c.total_content_length = c.total_content_length + :contentLength
            WHERE c.id = :chatId')
            ->execute([
                'chatId'        => $chat->getId(),
                'contentLength' => mb_strlen($updateProxy->getText()),
            ]);
    }

    /**
     * Возвращает кверибилдер для всех чатов бота
     *
     * @param Bot $bot
     * @return QueryBuilder
     */
    protected function getQbForBot(Bot $bot): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c');
        $qb
            ->where('c.isPrivate = false')
            ->andWhere('c.id IN (SELECT IDENTITY(m.chat) FROM \App\Entity\BotsInChats m WHERE m.bot = :bot AND m.is_active = true)')
            ->setParameter('bot', $bot);

        return $qb;
    }

    public function getNew(Bot $bot, int $maxResult = 4): ?array
    {
        return $this->getQbForBot($bot)
            ->orderBy('c.id', 'DESC')
            ->setMaxResults($maxResult)
            ->getQuery()
            ->getResult();
    }

    /**
     * Возвращает чат, которому необходимо провести обновление юзерпика
     *
     * @param Bot $bot
     * @return Chat|null
     * @throws NonUniqueResultException
     */
    public function getOutdatedUserpic(Bot $bot): ?Chat
    {
        $qb = $this->createQueryBuilder('c');
        $qb
            ->where('c.lastUserpicUpdate < :updateTolerance OR c.lastUserpicUpdate IS NULL')
            ->setParameter('updateTolerance', Carbon::now()->subDays(7))
            ->andWhere('c.id IN (SELECT IDENTITY(m.chat) FROM \App\Entity\BotsInChats m WHERE m.bot = :bot AND m.is_active = true)')
            ->andWhere('c.isPrivate = false')
            ->setParameter('bot', $bot)
            ->orderBy('c.lastUserpicUpdate', 'ASC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}