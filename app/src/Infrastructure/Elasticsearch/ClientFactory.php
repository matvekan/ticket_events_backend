<?php

declare(strict_types=1);

namespace App\Infrastructure\Elasticsearch;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

final class ClientFactory
{
    public static function create(): Client
    {
        $hosts = array_map('trim', explode(',', $_SERVER['ELASTICSEARCH_HOSTS'] ?? 'http://elasticsearch:9200'));

        return ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
    }
}
