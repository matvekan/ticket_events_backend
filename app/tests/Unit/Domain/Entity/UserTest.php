<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\User;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Role;
use App\Domain\ValueObject\UserId;
use App\Tests\Unit\Domain\Support\DomainFixture;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testCreateInitializesProfileWithDefaultUserRole(): void
    {
        $user = $this->createUser();

        self::assertSame('user@example.com', $user->email()->toString());
        self::assertSame([Role::User], $user->roles());
        self::assertNull($user->password());
    }

    public function testCreateExposesEmailAsSecurityIdentifier(): void
    {
        $user = $this->createUser('owner@example.com');

        self::assertSame('owner@example.com', $user->identifier());
        self::assertSame('owner@example.com', $user->getUserIdentifier());
    }

    public function testChangeRolesAssignsAdminRoleToExistingUser(): void
    {
        $user = $this->createUser();

        $user->changeRoles([Role::Admin->value]);

        self::assertSame([Role::Admin->value], $user->roles());
    }

    public function testChangeRolesRemovesDuplicateRolesWhenAssigning(): void
    {
        $user = $this->createUser();

        $user->changeRoles([Role::Admin->value, Role::Admin->value]);

        self::assertSame([Role::Admin->value], $user->roles());
    }

    public function testChangeRolesThrowsBusinessRuleViolationForUnknownRole(): void
    {
        $user = $this->createUser();

        $this->expectException(BusinessRuleViolationException::class);

        $user->changeRoles(['ROLE_SUPER']);
    }

    public function testChangeRolesThrowsBusinessRuleViolationForNonStringRole(): void
    {
        $user = $this->createUser();

        $this->expectException(BusinessRuleViolationException::class);

        $user->changeRoles([42]);
    }

    public function testPasswordResetTokenIsValidBeforeExpiryDate(): void
    {
        $user = $this->createUserWithToken('2026-06-02T12:00:00Z');

        self::assertTrue($user->isPasswordResetTokenValid($this->fixedNow()));
    }

    public function testPasswordResetTokenIsInvalidAfterExpiryDate(): void
    {
        $user = $this->createUserWithToken('2026-05-01T12:00:00Z');

        self::assertFalse($user->isPasswordResetTokenValid($this->fixedNow()));
    }

    public function testPasswordResetTokenIsInvalidWhenTokenWasNeverSet(): void
    {
        $user = $this->createUser();

        self::assertFalse($user->isPasswordResetTokenValid($this->fixedNow()));
    }

    public function testClearPasswordResetTokenRemovesStoredTokenAndExpiry(): void
    {
        $user = $this->createUserWithToken('2026-06-02T12:00:00Z');

        $user->clearPasswordResetToken();

        self::assertNull($user->resetTokenHash());
        self::assertNull($user->resetTokenExpiresAt());
        self::assertFalse($user->isPasswordResetTokenValid($this->fixedNow()));
    }

    public function testChangePasswordStoresProvidedPasswordHash(): void
    {
        $user = $this->createUser();

        $user->changePassword('hashed-secret');

        self::assertSame('hashed-secret', $user->password());
    }

    private function createUser(string $email = 'user@example.com'): User
    {
        $ids = DomainFixture::ids();

        return User::create(new UserId($ids->generate()), new Name('Test User'), new Email($email));
    }

    private function createUserWithToken(string $expiresAt): User
    {
        $user = $this->createUser();

        $user->setPasswordResetToken('token-hash', new \DateTimeImmutable($expiresAt));

        return $user;
    }

    private function fixedNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-06-01T12:00:00Z');
    }
}
