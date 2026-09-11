<?php

declare(strict_types=1);

namespace App\Infrastructure\WebSocket\Dto;

final class FrameParser
{
    public function parse(string $payload): ?IncomingFrame
    {
        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($data)) {
            return null;
        }

        $roomId = $data['roomId'] ?? null;
        if (! is_string($roomId) || $roomId === '' || ! preg_match('/^[0-9a-f-]{36}$/i', $roomId)) {
            return null;
        }

        return match ($data['type'] ?? null) {
            'subscribe' => new SubscribeFrame($roomId),
            'unsubscribe' => new UnsubscribeFrame($roomId),
            'message' => $this->messageFrame($roomId, $data['text'] ?? null),
            default => null,
        };
    }

    private function messageFrame(string $roomId, mixed $text): ?MessageFrame
    {
        if (! is_string($text) || trim($text) === '') {
            return null;
        }

        return new MessageFrame($roomId, $text);
    }
}
