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
    description: 'Seeds the database with demo data: venues, events, users and orders.',
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
        $io->title('Demo data');

        $fixtures = $this->fixturesLoader->getFixtures();
        if (count($fixtures) === 0) {
            $io->warning('No fixtures found.');
            return Command::FAILURE;
        }

        $executor = new ORMExecutor($this->entityManager);
        $executor->execute($fixtures, append: true);

        $io->success(sprintf(
            'Seeding finished (%d fixtures). Demo accounts: admin@tickets.by / admin1234 (admin), demo@tickets.by / demo1234.',
            count($fixtures),
        ));

        return Command::SUCCESS;
    }
}