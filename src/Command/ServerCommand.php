<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:server',
    description: 'Start Symfony development server'
)]
class ServerCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setHelp('This command starts the Symfony development server.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Starting Development Environment');

        // Start Symfony server
        $io->section('Starting Symfony Server');
        $io->success('🚀 Starting Symfony development server...');
        
        $symfonyProcess = new Process(['php', 'bin/console', 'server:run']);
        $symfonyProcess->setTty(true);
        $symfonyProcess->run();

        return Command::SUCCESS;
    }
}
