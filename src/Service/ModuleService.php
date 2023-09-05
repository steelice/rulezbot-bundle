<?php

namespace Rulezdev\RulezbotBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Rulezdev\RulezbotBundle\Entity\Chat;
use Rulezdev\RulezbotBundle\Entity\ChatModule;
use Rulezdev\RulezbotBundle\Repository\BotModuleRepository;
use Rulezdev\RulezbotBundle\Repository\ChatModuleRepository;
use Symfony\Component\Finder\Finder;

class ModuleService
{
    public function __construct(
        private readonly string                 $modulesPath,
        private readonly BotModuleRepository    $moduleRepository,
        private readonly ChatModuleRepository   $chatModuleRepository,
        private readonly EntityManagerInterface $em
    )
    {
    }

    /**
     * Возвращает список доступных классов
     *
     * @return array
     */
    public function getModuleFiles(): array
    {
        $fileFinder = new Finder();
        $fileFinder->files()
            ->in($this->modulesPath)
            ->notName(['AbstractBotModule.php', 'BotModuleInterface.php'])
            ->depth(0);
        $classes = [];
        foreach ($fileFinder as $file) {
            $classes[] = pathinfo($file->getPathname(), PATHINFO_FILENAME);
        }

        return $classes;
    }

    /**
     * @return BotModuleRepository
     */
    public function getModuleRepository(): BotModuleRepository
    {
        return $this->moduleRepository;
    }

    /**
     * @param Chat $chat
     * @return ChatModule[]
     */
    public function modulesForChat(Chat $chat): array
    {
        $chatModules = $this->chatModuleRepository->getModulesForChat($chat);
        $exists = array_map(static fn(ChatModule $item) => $item->getModule()->getId(), $chatModules);

        $available = $this->moduleRepository->findAll();
        foreach ($available as $avModule) {
            if (!in_array($avModule->getId(), $exists, true)) {
                $chatModule = new ChatModule($chat, $avModule);
                $this->em->persist($chatModule);
            }
        }

        $this->em->flush();

        return $this->chatModuleRepository->getModulesForChat($chat);
    }

    /**
     * Возвращает для модуля все его чаты
     *
     * @param string $alias
     * @return ChatModule[]
     */
    public function chatsForModule(string $alias): array
    {
        return $this->em->createQueryBuilder()
            ->select('cm, c')
            ->from(ChatModule::class, 'cm')
            ->join('cm.module', 'm')
            ->join('cm.chat', 'c')
            ->andWhere('m.alias = :alias')
            ->andWhere('cm.isEnabled = true AND m.isEnabledGlobal = true')
            ->andWhere('c.isPrivate = false')
            ->setParameter('alias', $alias)
            ->orderBy('cm.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}