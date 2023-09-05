<?php

namespace Rulezdev\RulezbotBundle\Helper;

use MessageFormatter;

class TextFormat
{
    public static function plural($n, $one, $two, $five): string
    {
        return MessageFormatter::formatMessage('ru', sprintf('{0, plural,
            one{%s}
            few{%s}
            other{%s} 
        }', $one, $two, $five), [$n]);
    }

    public static function spellout($n, $format = '%spellout-numbering'): string
    {
        return self::format('{0, spellout,' . $format . '}', [$n]);
    }

    public static function format(string $template, $data): string
    {
        return MessageFormatter::formatMessage('ru', $template, $data);
    }

    /**
     * Убирает лишние пробелы, если надо приводит к нижнему кейсу
     */
    public static function normalizeText(string $text, bool $lower = true): string
    {
        $text = trim($text);
        $text = preg_replace('~ {2,}~', ' ', $text);
        if ($lower) {
            $text = mb_strtolower($text);
        }

        return $text;
    }

    /**
     * Возвращает чистый текст, без смайлов, спец-символов и прочего
     */
    public static function onlyText($text, bool $lower = true): string
    {
        $text = self::normalizeText($text, $lower);
        $text = preg_replace('~[^А-я\da-zA-Z.,?:;@\s]~ui', ' ', $text);
        $text = preg_replace('~ {2,}~', ' ', $text);
        $text = trim($text, ' ?!,.:()/%=-');

        return $text;
    }

    /**
     * Превращает список текстов из текстового вида в массив уникальных значений.
     *
     * @param string $texts
     * @return array
     */
    public static function textToArray(string $texts): array
    {
        return array_unique(array_filter(array_map('trim', explode("\n", $texts))));
    }
}