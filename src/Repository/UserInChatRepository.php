<?php

namespace Rulezdev\RulezbotBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Rulezdev\RulezbotBundle\Entity\Chat;
use Rulezdev\RulezbotBundle\Entity\User;
use Rulezdev\RulezbotBundle\Entity\UserInChat;

/**
 * @method UserInChat|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserInChat|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserInChat[]    findAll()
 * @method UserInChat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserInChatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserInChat::class);
    }

    /**
     * @param \Rulezdev\RulezbotBundle\Entity\Chat $chat
     * @return UserInChat[]|null
     */
    public function getActiveUsers(\Rulezdev\RulezbotBundle\Entity\Chat $chat, ?bool $noPidor = null): ?array
    {
        $qb = $this->createQueryBuilder('uic')
            ->where('uic.chat = :chat')
            ->andWhere('uic.msgRole >= 0')
            ->setParameter('chat', $chat)
            ->orderBy('uic.lastSeenAt', 'DESC')
            ->setMaxResults(10);

        if ($noPidor !== null) {
            $qb
                ->andWhere('uic.noPidor = :noPidor')
                ->setParameter('noPidor', $noPidor);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Возвращает пользователя в чате. Если его нет - создаёт.
     */
    public function getOrCreate(User $user, Chat $chat, bool $flush = true): UserInChat
    {
        $uic = $this->get($user, $chat);

        if (!$uic) {
            $uic = new UserInChat($chat, $user);
            $this->_em->persist($uic);
            if ($flush) {
                $this->_em->flush();
            }
        }

        return $uic;
    }

    /**
     * Возвращает пользователя
     *
     * @param User $user
     * @param Chat $chat
     * @return UserInChat|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function get(User $user, Chat $chat): ?UserInChat
    {
        return $this->createQueryBuilder('uic')
            ->where('uic.chat = :chat AND uic.user = :user')
            ->setParameters([
                'user' => $user,
                'chat' => $chat,
            ])->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }

    /**
     * Возвращает случайного пользователя, который был недавно активен
     *
     * @param Chat $chat
     * @return UserInChat|null
     */
    public function getRandomActiveUser(Chat $chat, ?bool $noPidor = null): ?UserInChat
    {
        $activeUsers = $this->getActiveUsers($chat, $noPidor);
        if (!$activeUsers) {
            return null;
        }
        shuffle($activeUsers);

        return $activeUsers[0];
    }

    public function getAvgRatingForChat(Chat $chat)
    {
        return $this->createQueryBuilder('uic')
            ->select('AVG(uic.totalRating)')
            ->where('uic.chat = :chat')
            ->setParameter('chat', $chat)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param User $user
     * @return ?UserInChat[]
     */
    public function getChatsForUser(User $user): ?array
    {
        $qb = $this->createQueryBuilder('uic')
            ->where('uic.user = :user')
            ->setParameter('user', $user)
            ->orderBy('uic.msgRole', 'DESC')
            ->addOrderBy('uic.lastSeenAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function getAllChatRoles(User $user): array
    {
        $data = $this->createQueryBuilder('uic')
            ->select(['uic.msgRole', 'IDENTITY(uic.chat) as chatId'])
            ->where('uic.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getScalarResult();

        return array_combine(
            array_column($data, 'chatId'),
            array_column($data, 'msgRole')
        );
    }

    public function save(UserInChat $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
