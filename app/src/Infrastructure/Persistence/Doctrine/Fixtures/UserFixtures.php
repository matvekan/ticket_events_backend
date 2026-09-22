<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\UserId;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

final class UserFixtures extends Fixture
{
    private Generator $faker;

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly IdGeneratorInterface $ids,
    ) {
        $this->faker = Factory::create();
    }

    public function load(ObjectManager $manager): void
    {
        $this->createDemoUser('Admin', 'admin@tickets.by', 'admin1234', ['ROLE_ADMIN'], $manager);
        $this->createDemoUser('Demo User', 'demo@tickets.by', 'demo1234', [], $manager);

        for ($i = 1; $i <= 5; ++$i) {
            $email = \sprintf('user%d@tickets.by', $i);
            if ($this->users->findByEmail(new Email($email)) !== null) {
                $this->addReference(\sprintf('user_%d', $i), $this->users->findByEmail(new Email($email)));

                continue;
            }

            $user = User::create(
                new UserId($this->ids->generate()),
                new Name(\sprintf('%s %s', $this->faker->firstName(), $this->faker->lastName())),
                new Email($email),
            );
            $user->changePassword(password_hash($this->faker->password(8), \PASSWORD_DEFAULT));
            $user->changeRoles(['ROLE_USER']);

            $this->users->save($user);
            $manager->flush();

            $this->addReference(\sprintf('user_%d', $i), $user);
        }
    }

    private function createDemoUser(string $name, string $email, string $password, array $roles, ObjectManager $manager): void
    {
        $existing = $this->users->findByEmail(new Email($email));
        if ($existing !== null) {
            $this->addReference($email === 'admin@tickets.by' ? 'user_admin' : 'user_demo', $existing);

            return;
        }

        $user = User::create(
            new UserId($this->ids->generate()),
            new Name($name),
            new Email($email),
        );
        $user->changePassword(password_hash($password, \PASSWORD_DEFAULT));
        $user->changeRoles(array_merge(['ROLE_USER'], $roles));

        $this->users->save($user);
        $manager->flush();

        $this->addReference($email === 'admin@tickets.by' ? 'user_admin' : 'user_demo', $user);
    }
}
