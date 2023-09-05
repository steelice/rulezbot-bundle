<?php

namespace Rulezdev\RulezbotBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Rulezdev\RulezbotBundle\Entity\Chat;
use Rulezdev\RulezbotBundle\Entity\ChatModule;

/**
 * @method ChatModule|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChatModule|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChatModule[]    findAll()
 * @method ChatModule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChatModuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatModule::class);
    }

    /**
     * Возвращает список модулей для чата в порядке их исполнения
     *
     * @param Chat $chat
     * @return ChatModule[]
     */
    public function getModulesForChat(Chat $chat): array
    {
        return $this->createQueryBuilder('cm')
            ->leftJoin('cm.module', 'km')
            ->where('cm.chat = :chat')
            ->setParameter('chat', $chat)
            ->orderBy('km.priority')
            ->getQuery()
            ->getResult();
    }


}
