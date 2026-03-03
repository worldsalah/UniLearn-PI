<?php
require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpFoundation\Request;
use App\Service\GeminiAiService;

class TestKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir() . '/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if ($envs['all'] ?? false || $envs[$this->environment] ?? false) {
                yield new $class();
            }
        }
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->setParameter('container.dumper.inline_class_loader', true);
        $confDir = $this->getProjectDir() . '/config';
        $loader->load($confDir . '/{packages}/*.{php,yaml,yml}', 'glob');
        $loader->load($confDir . '/{packages}/' . $this->environment . '/*.{php,yaml,yml}', 'glob');
        $loader->load($confDir . '/{services}.{php,yaml,yml}', 'glob');
        $loader->load($confDir . '/{services}_' . $this->environment . '.{php,yaml,yml}', 'glob');
    }

    public function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = $this->getProjectDir() . '/config';
        $routes->import($confDir . '/{routes}/*.{php,yaml,yml}', '/', 'glob');
        $routes->import($confDir . '/{routes}/' . $this->environment . '/*.{php,yaml,yml}', '/', 'glob');
        $routes->import($confDir . '/{routes}.{php,yaml,yml}', '/', 'glob');
    }
}

$kernel = new TestKernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();

// Check if service exists
if ($container->has(GeminiAiService::class)) {
    $service = $container->get(GeminiAiService::class);
    echo "Service found!\n";
    
    // Test generating questions
    $result = $service->generateQuizQuestions('Test Course', 'A test course', 2);
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "Service not found!\n";
}
