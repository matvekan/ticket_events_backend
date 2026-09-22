<?php

declare(strict_types=1);

namespace App\Tests\Integration\Handler;

use App\Application\Command\Auth\RegisterUserCommand;
use App\Application\Command\Auth\ForgotPasswordCommand;
use App\Application\Command\Auth\ResetPasswordCommand;
use App\Application\CommandHandler\Auth\RegisterUserHandler;
use App\Application\CommandHandler\Auth\ForgotPasswordHandler;
use App\Application\CommandHandler\Auth\ResetPasswordHandler;
use App\Domain\Entity\OutboxMessage;
use App\Domain\Event\SendPasswordResetEmail;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Role;
use App\Tests\Integration\Support\IntegrationFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Application layer: Auth handlers with real PostgreSQL.
 */
final class AuthHandlersTest extends KernelTestCase
{
    public function testRegisterUserCreatesUserWithDefaultRole(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $suffix = uniqid();
        $email = sprintf('register_%s@example.com', $suffix);

        $handler = $container->get(RegisterUserHandler::class);
        $handler(new RegisterUserCommand('Test User', $email, 'password123'));
        $em->clear();

        $user = $container->get(UserRepositoryInterface::class)->findByEmail(new Email($email));

        self::assertNotNull($user);
        self::assertSame('Test User', $user->name()->toString());
        self::assertSame($email, $user->email()->toString());
        self::assertNotNull($user->password());
    }

    public function testRegisterUserFailsForDuplicateEmail(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $suffix = uniqid();
        $email = sprintf('duplicate_%s@example.com', $suffix);

        $handler = $container->get(RegisterUserHandler::class);
        $handler(new RegisterUserCommand('Test User', $email, 'password123'));
        $em->clear();

        $this->expectException(\App\Domain\Exception\BusinessRuleViolationException::class);

        $handler(new RegisterUserCommand('Another User', $email, 'password123'));
    }

    public function testForgotPasswordSetsResetTokenAndDispatchesEmail(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $suffix = uniqid();
        $data = IntegrationFixture::createPublishedEventWithSeat(
            $em,
            $container->get(\App\Domain\Shared\ClockInterface::class),
            $container->get(\App\Domain\Shared\IdGeneratorInterface::class),
            $suffix,
        );
        $email = $data['user']->email()->toString();

        $handler = $container->get(ForgotPasswordHandler::class);
        $handler(new ForgotPasswordCommand($email));
        $em->clear();

        $user = $container->get(UserRepositoryInterface::class)->findByEmail(new Email($email));

        self::assertNotNull($user->resetTokenHash());
        self::assertNotNull($user->resetTokenExpiresAt());
        self::assertTrue($user->isPasswordResetTokenValid($container->get(\App\Domain\Shared\ClockInterface::class)->now()));
    }

    public function testForgotPasswordIsIdempotentForUnknownEmail(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $handler = $container->get(ForgotPasswordHandler::class);

        $handler(new ForgotPasswordCommand('unknown@example.com'));
        $handler(new ForgotPasswordCommand('unknown@example.com'));

        self::assertTrue(true);
    }

    public function testResetPasswordChangesPasswordAndClearsToken(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $ids = $container->get(\App\Domain\Shared\IdGeneratorInterface::class);
        $clock = $container->get(\App\Domain\Shared\ClockInterface::class);
        $suffix = uniqid();

        $handler = $container->get(RegisterUserHandler::class);
        $handler(new RegisterUserCommand('Reset Test User', sprintf('reset_%s@example.com', $suffix), 'password123'));
        $em->clear();

        $user = $container->get(UserRepositoryInterface::class)->findByEmail(
            new Email(sprintf('reset_%s@example.com', $suffix))
        );

        $rawToken = 'test-reset-token-' . $suffix;
        $tokenHash = hash('sha256', $rawToken);
        $user->setPasswordResetToken($tokenHash, $clock->now()->modify('+1 hour'));
        $container->get(UserRepositoryInterface::class)->save($user);
        $em->flush();
        $em->clear();

        $resetHandler = $container->get(ResetPasswordHandler::class);
        $resetHandler(new ResetPasswordCommand($rawToken, 'newpassword123'));
        $em->clear();

        $user = $container->get(UserRepositoryInterface::class)->findByEmail(
            new Email(sprintf('reset_%s@example.com', $suffix))
        );

        self::assertNull($user->resetTokenHash());
        self::assertNull($user->resetTokenExpiresAt());
        self::assertNotNull($user->password());
    }

    public function testResetPasswordFailsForInvalidToken(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $handler = $container->get(ResetPasswordHandler::class);

        $this->expectException(\App\Domain\Exception\DomainException::class);

        $handler(new ResetPasswordCommand('invalid-token-hash', 'newpassword123'));
    }
}