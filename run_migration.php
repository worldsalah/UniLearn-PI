<?php

require_once 'vendor/autoload.php';

use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

$application = new Application('migrations');
$application->add(new MigrateCommand());

try {
    $input = new StringInput('migrate');
    $output = new BufferedOutput();
    $application->run($input, $output);
    
    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
