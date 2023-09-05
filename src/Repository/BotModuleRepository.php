<?php

namespace Rulezdev\RulezbotBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Rulezdev\RulezbotBundle\Entity\BotModule;
use Rulezdev\RulezbotBundle\Entity\Chat;
use Rulezdev\RulezbotBundle\Entity\ChatModule;

/**
 * @method BotModule|null find($id, $lockMode = null, $lockVersion = null)
 * @method BotModule|null findOneBy(array $criteria, array $orderBy = null)
 * @method BotModule[]    findAll()
 * @method BotModule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BotModuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BotModule::class);
    }

    public function getModule($alias, $className): ?BotModule
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.alias = :alias OR m.className = :class')
            ->setParameters([
                'alias' => $alias,
                'class' => $className
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Chat $chat
     * @return BotModule[]
     */
    public function getModulesForChat(Chat $chat): array
    {
        return $this->createQueryBuilder('km')
            ->join(ChatModule::class, 'cm')
            ->where('cm.chat = :chat')
            ->setParameter('chat', $chat)
            ->orderBy('km.priority')
            ->getQuery()
            ->getResult();
    }

}