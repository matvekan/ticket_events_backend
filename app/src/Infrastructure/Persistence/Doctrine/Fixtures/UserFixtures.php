<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Application\Command\Auth\RegisterUserCommand;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\Messenger\MessageBusInterface;

final class UserFixtures extends Fixture
{
    private Generator $faker;

    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly UserRepositoryInterface $users,
    ) {
        $this->faker = Factory::create();
    }

    public function load(ObjectManager $manager): void
    {
        $this->createDemoUser('Admin', 'admin@tickets.by', 'admin1234', ['ROLE_ADMIN']);
        $this->createDemoUser('Demo User', 'demo@tickets.by', 'demo1234');

        for ($i = 1; $i <= 5; ++$i) {
            $email = sprintf('user%d@tickets.by', $i);
            if ($this->users->findByEmail(new Email($email)) !== null) {
                $this->addReference(sprintf('user_%d', $i), $this->users->findByEmail(new Email($email)));
                continue;
            }

            $this->commandBus->dispatch(new RegisterUserCommand(
                name: sprintf('%s %s', $this->faker->firstName(), $this->faker->lastName()),
                email: $email,
                password: $this->faker->password(8),
            ));

            $user = $this->users->findByEmail(new Email($email));
            if ($user !== null) {
                $this->addReference(sprintf('user_%d', $i), $user);
            }
        }
    }

    private function createDemoUser(string $name, string $email, string $password, array $roles = []): void
    {
        $existing = $this->users->findByEmail(new Email($email));
        if ($existing !== null) {
            $this->addReference($email === 'admin@tickets.by' ? 'user_admin' : 'user_demo', $existing);
            return;
        }

        $this->commandBus->dispatch(new RegisterUserCommand(
            name: $name,
            email: $email,
            password: $password,
        ));

        $user = $this->users->findByEmail(new Email($email));
        if ($user !== null && $roles !== []) {
            $user->updateRoles([...$roles, 'ROLE_USER']);
            $this->users->save($user);
        }

        $this->addReference($email === 'admin@tickets.by' ? 'user_admin' : 'user_demo', $user);
    }
}
