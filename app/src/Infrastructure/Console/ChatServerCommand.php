<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket\InternetAddress;
use Amp\Websocket\Server\Rfc6455Acceptor;
use Amp\Websocket\Server\Websocket;
use App\Infrastructure\WebSocket\ChatWebSocketHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Revolt\EventLoop;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:chat-server',
    description: 'Runs the support chat WebSocket server (port from CHAT_WS_PORT, default 8080).',
)]
final class ChatServerCommand extends Command
{
    public function __construct(
        private readonly ChatWebSocketHandler $handler,
        private readonly string $chatWsBind,
        private readonly int $chatWsPort,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new Logger('chat');
        $handler = new StreamHandler(\STDOUT, Logger::DEBUG);
        $handler->setFormatter(new LineFormatter("[%datetime%] %channel%.%level_name%: %message%\n"));
        $logger->pushHandler($handler);

        $server = SocketHttpServer::createForDirectAccess($logger);
        $server->expose(new InternetAddress($this->chatWsBind, $this->chatWsPort));

        $websocket = new Websocket($server, $logger, new Rfc6455Acceptor(), $this->handler);

        $server->start($websocket, new DefaultErrorHandler());
        $output->writeln(sprintf('Chat WebSocket server listening on %s:%d', $this->chatWsBind, $this->chatWsPort));

        EventLoop::run();

        return Command::SUCCESS;
    }
}
