<?php

declare(strict_types=1);

namespace App\Infrastructure\Elasticsearch;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

final class ClientFactory
{
    /**
     * @param array<int, string> $hosts
     */
    public function __construct(
        private readonly array $hosts,
    ) {
    }

    public function create(): Client
    {
        return ClientBuilder::create()
            ->setHosts($this->hosts)
            ->build();
    }
}
