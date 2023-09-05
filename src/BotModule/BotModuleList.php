<?php

namespace Rulezdev\RulezbotBundle\BotModule;

use Symfony\Component\Finder\Finder;

class BotModuleList
{
    /**
     * Возвращает список файлов (без путей) для модулей
     * @param string $dir
     * @return array
     */
    public static function getFiles(string $dir): array
    {
        $fileFinder = new Finder();
        $fileFinder->files()
            ->in($dir)
            ->notName(['AbstractBotModule.php', 'BotModuleInterface.php', 'BotModuleList.php'])
            ->depth(0);

        $files = [];
        foreach ($fileFinder as $file) {
            $files[] = pathinfo($file->getPathname(), PATHINFO_FILENAME);
        }

        return $files;
    }

    /**
     * Возвращает список классов модулей бота
     *
     * @param string $dir
     * @return array
     */
    public static function getClassList(string $dir): array
    {
        $list = self::getFiles($dir);
        $classes = [];
        foreach ($list as $file) {
            $className = 'App\BotModule\\' . $file;
            $classes[$className] = $className;
        }

        return $classes;
    }
}