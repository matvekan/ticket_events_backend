<?php

declare(strict_types=1);

namespace App\Application\Event;

interface EventBusInterface
{
    public function dispatch(object $event): void;
}
