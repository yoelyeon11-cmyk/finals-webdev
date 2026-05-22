<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:seed-test-customers',
    description: 'Create or update test customer accounts for mobile app testing',
)]
final class SeedTestCustomersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $accounts = [
            ['username' => 'customer', 'email' => 'customer@cloudrobe.com', 'fullName' => 'Test Customer', 'password' => 'customer123'],
            ['username' => 'demo_customer', 'email' => 'demo@cloudrobe.com', 'fullName' => 'Demo Customer', 'password' => 'demo123'],
        ];

        foreach ($accounts as $row) {
            $user = $this->users->findOneBy(['username' => $row['username']]) ?? new User();
            $user->setUsername($row['username']);
            $user->setEmail($row['email']);
            $user->setFullName($row['fullName']);
            $user->setRoles(['ROLE_USER']);
            $user->setIsVerified(true);
            $user->setPassword($this->passwordHasher->hashPassword($user, $row['password']));
            $this->em->persist($user);
            $io->writeln(sprintf('  <info>%s</info> / %s', $row['username'], $row['password']));
        }

        $this->em->flush();
        $io->success('Test customers ready. Sign in with username + password (not email).');

        return Command::SUCCESS;
    }
}
