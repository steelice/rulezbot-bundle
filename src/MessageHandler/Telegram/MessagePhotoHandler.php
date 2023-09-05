<?php

namespace Rulezdev\RulezbotBundle\MessageHandler\Telegram;

use Rulezdev\RulezbotBundle\Message\Telegram\Photo;
use Rulezdev\RulezbotBundle\Service\BotService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class MessagePhotoHandler
{
    public function __construct(
        private readonly BotService      $bot,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function __invoke(Photo $msg)
    {
        if (is_array($msg->photo)) {
            shuffle($msg->photo);
            $msg->photo = $msg->photo[0];
        }

        $photo = $msg->photo;
        if (str_starts_with($photo, '@')) {
            $photo = mb_substr($photo, 1);
            if (!file_exists($photo)) {
                throw new \Exception('File ' . $photo . ' not exists!');
            }
            $photo = new \CURLFile($photo);
        }

        try {
            $this->bot->api->sendPhoto(
                $msg->chatId,
                $photo,
                $msg->caption,
                null,
                $msg->replyToMessageId,
                $msg->replyMarkup,
                $msg->disableNotification,
                $msg->parseMode
            );
        } catch (\Exception $e) {
            $this->logger->error('Cant send photo', ['exception' => $e, 'photo' => $photo]);
        }

        if ($msg->deleteAfterSend && ($photo instanceof \CURLFile)) {
            try {
//                @unlink($photo->getFilename());
            } catch (\Exception $e) {
            }
        }
    }
}