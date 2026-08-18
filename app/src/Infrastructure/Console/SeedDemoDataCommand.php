<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-demo-data',
    description: 'Заполняет базу демо-данными: реальные площадки Минска, события, пользователи и заказы.',
)]
final class SeedDemoDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SymfonyFixturesLoader $fixturesLoader,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Демо-данные');

        $fixtures = $this->fixturesLoader->getFixtures();
        if (count($fixtures) === 0) {
            $io->warning('Фикстуры не найдены.');
            return Command::FAILURE;
        }

        $executor = new ORMExecutor($this->entityManager);
        $executor->execute($fixtures, append: true);

        $io->success(sprintf(
            'Сид завершён (%d фикстур). Демо-аккаунты: admin@tickets.by / admin1234 (админ), demo@tickets.by / demo1234, anna@tickets.by / anna1234.',
            count($fixtures),
        ));

        return Command::SUCCESS;
    }
}