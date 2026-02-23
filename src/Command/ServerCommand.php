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
    description: 'Start Symfony server with Elasticsearch automatically'
)]
class ServerCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setHelp('This command starts both Symfony server and Elasticsearch automatically.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Starting Development Environment');

        // Check if Elasticsearch is already running
        $io->section('Checking Elasticsearch');
        
        $checkProcess = new Process(['curl', '-s', 'http://localhost:9200/_cluster/health']);
        $checkProcess->run();
        
        if ($checkProcess->isSuccessful()) {
            $io->success('✅ Elasticsearch is already running');
        } else {
            $io->text('🔧 Starting Elasticsearch...');
            
            // Start Elasticsearch using Docker
            $elasticsearchProcess = new Process([
                'docker', 'run', '-d', 
                '--name', 'unilearn-elasticsearch',
                '-p', '9200:9200',
                '-e', 'discovery.type=single-node',
                '-e', 'xpack.security.enabled=false',
                'elasticsearch:8.11.0'
            ]);
            
            $elasticsearchProcess->run();
            
            if ($elasticsearchProcess->isSuccessful()) {
                $io->success('✅ Elasticsearch started successfully');
            } else {
                $io->error('❌ Failed to start Elasticsearch');
                $io->text('Make sure Docker is installed and running');
                return Command::FAILURE;
            }
            
            // Wait for Elasticsearch to be ready
            $io->text('⏳ Waiting for Elasticsearch to be ready...');
            $this->waitForElasticsearch($io);
        }

        // Start Symfony server
        $io->section('Starting Symfony Server');
        $io->success('🚀 Starting Symfony development server...');
        
        $symfonyProcess = new Process(['php', 'bin/console', 'server:run']);
        $symfonyProcess->setTty(true);
        $symfonyProcess->run();

        return Command::SUCCESS;
    }

    private function waitForElasticsearch(SymfonyStyle $io): void
    {
        $maxAttempts = 30;
        $attempt = 0;
        
        while ($attempt < $maxAttempts) {
            $checkProcess = new Process(['curl', '-s', 'http://localhost:9200/_cluster/health']);
            $checkProcess->run();
            
            if ($checkProcess->isSuccessful()) {
                $io->success('✅ Elasticsearch is ready!');
                return;
            }
            
            $attempt++;
            sleep(2);
        }
        
        $io->warning('⚠️ Elasticsearch took too long to start, but continuing...');
    }
}
