<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Auth;

use App\Application\Command\Auth\RegisterUserCommand;
use App\Application\Command\CommandHandlerInterface;
use App\Application\Service\Auth\UserService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RegisterUserHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserService $userService,
    ) {
    }

    public function __invoke(RegisterUserCommand $command): void
    {
        $this->userService->register($command->name, $command->email, $command->password);
    }
}
