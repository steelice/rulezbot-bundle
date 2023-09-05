<?php

namespace Rulezdev\RulezbotBundle\Repository;

use Carbon\Carbon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Rulezdev\RulezbotBundle\Entity\User;
use TelegramBot\Api\Types\User as TgUser;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Возвращает пользователя, которому необходимо провести обновление юзерпика
     *
     * @return User|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getOutdatedUserpic(): ?User
    {
        $qb = $this->createQueryBuilder('c');
        $qb
            ->where('c.lastUserpicUpdate < :updateTolerance OR c.lastUserpicUpdate IS NULL')
            ->setParameter('updateTolerance', Carbon::now()->subDays(10))
            ->orderBy('c.lastUserpicUpdate', 'ASC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Возвращает пользователя по его телеграмным данным. Если пользователя нет, он будет создан
     */
    public function getOrCreateUser(TgUser $tgData): User
    {
        $user = $this->createQueryBuilder('u')
            ->where('u.msgId = :msgId')
            ->setParameter('msgId', $tgData->getId())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$user) {
            $how2Call = trim(($tgData->getFirstName() ?? '') . ' ' . ($tgData->getFirstName() ?? ''));
            if (!$how2Call && !empty($tgData->getUsername())) {
                $how2Call = $tgData->getUsername();
            }
            $this->getEntityManager()->getConnection()->prepare('INSERT INTO user SET 
                msg_id = :msgId, 
                msg_username = :username, 
                msg_first_name = :firstName,
                msg_last_name = :lastName,
                lang = :lang,
                how2call = :howCall 
                ')
                ->executeStatement([
                    'msgId'     => $tgData->getId(),
                    'username'  => $tgData->getUsername() ?: null,
                    'firstName' => $tgData->getFirstName() ?: '',
                    'lastName'  => $tgData->getLastName() ?: '',
                    'lang'      => $tgData->getLanguageCode() ?: 'ru',
                    'howCall'   => $how2Call
                ]);
            $user = $this->createQueryBuilder('u')
                ->where('u.msgId = :msgId')
                ->setParameter('msgId', $tgData->getId())
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        return $user;
    }
}