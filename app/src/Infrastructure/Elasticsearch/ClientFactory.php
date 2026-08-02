<?php

declare(strict_types=1);

namespace App\Infrastructure\Elasticsearch;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

final class ClientFactory
{
    public function __construct(
        private readonly string $hosts,
    ) {
    }

    public function create(): Client
    {
        $hosts = array_map('trim', explode(',', $this->hosts));

        return ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
    }
}
