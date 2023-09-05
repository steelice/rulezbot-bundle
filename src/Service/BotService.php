<?php

namespace Rulezdev\RulezbotBundle\Service;

use Rulezdev\RulezbotBundle\Message\Telegram\Animation;
use Rulezdev\RulezbotBundle\Message\Telegram\Message;
use Rulezdev\RulezbotBundle\Message\Telegram\Photo;
use Rulezdev\RulezbotBundle\Message\Telegram\Sticker;
use Doctrine\ORM\EntityManagerInterface;
use Rulezdev\RulezbotBundle\Entity\Bot;
use Rulezdev\RulezbotBundle\Entity\ChatLog;
use Rulezdev\RulezbotBundle\Repository\BotRepository;
use Symfony\Component\Messenger\MessageBusInterface;
use TelegramBot\Api\BaseType;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Client;

/**
 * Работа с ботом в виде сервиса
 *
 */
class BotService
{
    public const PARSE_MODE_HTML = 'html';
    public const PARSE_MODE_MARKDOWN = 'markdown';

    /** @var Bot */
    public Bot $entity;
    /**
     * @var BotApi
     */
    public BotApi $api;

    protected ?Client $botClient = null;
    private ?ChatLog $initialMessage = null;

    public function __construct(
        private readonly string                 $botName,
        private readonly EntityManagerInterface $em,
        private readonly BotRepository          $botRepository,
        private readonly MessageBusInterface    $bus
    )
    {
        if (!$bot = $this->botRepository->getByName($this->botName)) {
            throw new \RuntimeException(sprintf('Bot "%s" not found in db. Please add it to bot table', $this->botName));
        }
        $this->entity = $bot;

        $this->api = new BotApi($this->entity->getToken());
    }

    /**
     * Возвращает клиента для работы с входящими сообщениями
     *
     * @return Client
     */
    public function getClient(): Client
    {
        if (!$this->botClient) {
            $this->botClient = new Client($this->entity->getToken());
        }

        return $this->botClient;
    }

    /**
     * @return Bot
     */
    public function getEntity(): Bot
    {
        return $this->entity;
    }

    public function sendMessage(
        $chatId,
        string|array $text,
        $parseMode = self::PARSE_MODE_MARKDOWN,
        $replyToMessageId = null,
        ?BaseType $replyMarkup = null,
        bool $disablePreview = false,
    ): bool
    {
        if (is_array($text)) {
            $text = $text[random_int(0, count($text) - 1)];
        }

        $this->bus->dispatch(new Message($chatId, $text, $parseMode, $disablePreview, $replyToMessageId, $replyMarkup));

        return true;
    }

    public function sendSticker(
        $chatId,
        string|array $sticker,
        $replyToMessageId = null,
        $replyMarkup = null,
        bool $disableNotification = false
    ): bool
    {
        if (is_array($sticker)) {
            $sticker = $sticker[random_int(0, count($sticker) - 1)];
        }

        if ($this->initialMessage) {
            $this->replyRepository->logReply($this->initialMessage, 'sticker', $sticker);
        }

        $this->bus->dispatch(new Sticker($chatId, $sticker, $replyToMessageId, $replyMarkup, $disableNotification));

        return true;
    }

    public function sendAnimation(
        $chatId,
        string|array $animation,
        ?string $caption = null,
        $replyToMessageId = null,
        $replyMarkup = null,
        bool $disableNotification = false
    ): bool
    {
        if (is_array($animation)) {
            $animation = $animation[random_int(0, count($animation) - 1)];
        }

        if ($this->initialMessage) {
            $this->replyRepository->logReply($this->initialMessage, 'animation', $animation);
        }

        $this->bus->dispatch(new Animation($chatId, $animation, $caption, $replyToMessageId, $replyMarkup, $disableNotification));

        return true;
    }

    public function sendPhoto(
        $chatId,
        string|array $photo,
        ?string $caption = null,
        $replyToMessageId = null,
        $replyMarkup = null,
        bool $disableNotification = false,
        bool $deleteAfterSend = false,
    ): bool
    {
        if (is_array($photo)) {
            $photo = $photo[random_int(0, count($photo) - 1)];
        }

        if ($this->initialMessage) {
            $this->replyRepository->logReply($this->initialMessage, 'photo', $photo);
        }

        $this->bus->dispatch(new Photo(
            $chatId,
            $photo,
            $caption,
            $replyToMessageId,
            $replyMarkup,
            $disableNotification,
            null,
            $deleteAfterSend
        ));

        return true;
    }

    public function resetWebhook(): string
    {
        return $this->api->setWebhook($this->entity->getWebhook());
    }

    public function setInitialMessage(ChatLog $logMessage): static
    {
        $this->initialMessage = $logMessage;

        return $this;
    }
}