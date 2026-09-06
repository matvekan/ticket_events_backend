<?php

declare(strict_types=1);

namespace App\Infrastructure\WebSocket;

use Amp\Websocket\WebsocketClient;

final class ChatSubscriptions
{
    
    private array $roomClients = [];

    
    private array $clientRooms = [];

    public function subscribe(WebsocketClient $client, string $roomId): void
    {
        $roomId = self::normalize($roomId);
        $clientId = $client->getId();

        $this->roomClients[$roomId][$clientId] = $client;

        if (!in_array($roomId, $this->clientRooms[$clientId] ?? [], true)) {
            $this->clientRooms[$clientId][] = $roomId;
        }
    }

    public function unsubscribe(WebsocketClient $client, string $roomId): void
    {
        $roomId = self::normalize($roomId);
        $clientId = $client->getId();

        unset($this->roomClients[$roomId][$clientId]);
        if (($this->roomClients[$roomId] ?? []) === []) {
            unset($this->roomClients[$roomId]);
        }

        $this->clientRooms[$clientId] = array_values(array_filter(
            $this->clientRooms[$clientId] ?? [],
            static fn (string $id): bool => $id !== $roomId,
        ));
    }

    public function detach(WebsocketClient $client): void
    {
        $clientId = $client->getId();

        foreach ($this->clientRooms[$clientId] ?? [] as $roomId) {
            unset($this->roomClients[$roomId][$clientId]);
            if (($this->roomClients[$roomId] ?? []) === []) {
                unset($this->roomClients[$roomId]);
            }
        }

        unset($this->clientRooms[$clientId]);
    }

    public function broadcast(string $roomId, string $payload): void
    {
        $roomId = self::normalize($roomId);
        $subscribers = $this->roomClients[$roomId] ?? [];

        foreach ($subscribers as $client) {
            try {
                $client->sendText($payload);
            } catch (\Throwable) {

            }
        }
    }

    private static function normalize(string $roomId): string
    {
        return strtolower(trim($roomId));
    }
}