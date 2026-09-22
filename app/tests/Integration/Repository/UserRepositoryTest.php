<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Application\Command\Auth\RegisterUserCommand;
use App\Application\CommandHandler\Auth\RegisterUserHandler;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Role;
use App\Tests\Integration\Support\IntegrationFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Infrastructure layer: DoctrineUserRepository with real PostgreSQL.
 */
final class UserRepositoryTest extends KernelTestCase
{
    public function testFindByEmailReturnsUserWithRoles(): void
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

        $user = $container->get(UserRepositoryInterface::class)->findByEmail($data['user']->email());

        self::assertNotNull($user);
        self::assertSame($data['user']->rawId(), $user->rawId());
        self::assertSame($data['user']->email()->toString(), $user->email()->toString());
    }

    public function testFindByEmailReturnsNullForUnknownEmail(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $user = $container->get(UserRepositoryInterface::class)->findByEmail(
            new Email('unknown@example.com')
        );

        self::assertNull($user);
    }

    public function testFindByIdReturnsUserWithAllAttributes(): void
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

        $user = $container->get(UserRepositoryInterface::class)->findById($data['user']->id());

        self::assertNotNull($user);
        self::assertSame($data['user']->rawId(), $user->rawId());
        self::assertSame($data['user']->name()->toString(), $user->name()->toString());
        self::assertSame($data['user']->email()->toString(), $user->email()->toString());
    }

    public function testSavePersistsRoleChanges(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $ids = $container->get(\App\Domain\Shared\IdGeneratorInterface::class);
        $clock = $container->get(\App\Domain\Shared\ClockInterface::class);
        $suffix = uniqid();

        $handler = $container->get(RegisterUserHandler::class);
        $handler(new RegisterUserCommand('Repo User', sprintf('repouser_%s@example.com', $suffix), 'password123'));
        $em->clear();

        $user = $container->get(UserRepositoryInterface::class)->findByEmail(
            new Email(sprintf('repouser_%s@example.com', $suffix))
        );

        $user->changeRoles([Role::Admin->value]);
        $container->get(UserRepositoryInterface::class)->save($user);
        $em->flush();
        $em->clear();

        $reloaded = $container->get(UserRepositoryInterface::class)->findById($user->id());
        self::assertSame([Role::Admin->value], $reloaded->roles());
    }
}