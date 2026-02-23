<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use FOS\ElasticaBundle\Index\IndexManager;
use FOS\ElasticaBundle\Provider\ProviderInterface;
use FOS\ElasticaBundle\Persister\ObjectPersister;

#[AsCommand(
    name: 'app:elasticsearch:populate',
    description: 'Populate Elasticsearch index with Course data'
)]
class PopulateElasticsearchCommand extends Command
{
    private IndexManager $indexManager;

    public function __construct(IndexManager $indexManager)
    {
        $this->indexManager = $indexManager;
        
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('reset', 'r', InputOption::VALUE_NONE, 'Reset the index before populating')
            ->addOption('index', 'i', InputOption::VALUE_OPTIONAL, 'Specify which index to populate', 'courses')
            ->setHelp('This command allows you to populate the Elasticsearch index with Course data from your database.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $indexName = $input->getOption('index');
        $reset = $input->getOption('reset');

        $io->title('Populating Elasticsearch Index');

        try {
            // Get the index
            $index = $this->indexManager->getIndex($indexName);
            
            if ($reset) {
                $io->section('Resetting Index');
                $index->delete();
                $io->success('Index deleted successfully');
                
                // Recreate index with mapping
                $index->create();
                $io->success('Index recreated with mapping');
            }

            $io->section('Note');
            $io->text('To populate the index, use the built-in FOSElasticaBundle command:');
            $io->text('php bin/console fos:elastica:populate --index=' . $indexName);

            // Get index stats
            $stats = $index->count();
            $io->section('Index Statistics');
            $io->table(
                ['Metric', 'Value'],
                [
                    ['Total Documents', $stats['count']],
                    ['Index Name', $indexName],
                    ['Status', 'Active']
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error(sprintf('Error populating Elasticsearch index: %s', $e->getMessage()));
            
            if ($io->isVerbose()) {
                $io->text($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }
}
