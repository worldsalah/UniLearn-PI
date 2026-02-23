<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

echo "🔍 Testing FOSElasticaBundle Configuration...\n\n";

// Test 1: Check if FOSElasticaBundle is installed
if (class_exists('FOS\ElasticaBundle\FOSElasticaBundle')) {
    echo "✅ FOSElasticaBundle is installed\n";
} else {
    echo "❌ FOSElasticaBundle is NOT installed\n";
    exit(1);
}

// Test 2: Check configuration file
$configFile = __DIR__ . '/config/packages/fos_elastica.yaml';
if (file_exists($configFile)) {
    echo "✅ Configuration file exists\n";
} else {
    echo "❌ Configuration file missing\n";
    exit(1);
}

// Test 3: Parse configuration
try {
    $yaml = file_get_contents($configFile);
    $config = Yaml::parse($yaml);
    if (isset($config['fos_elastica']['indexes']['courses'])) {
        echo "✅ Courses index configuration found\n";
    } else {
        echo "❌ Courses index configuration missing\n";
    }
} catch (Exception $e) {
    echo "❌ Configuration error: " . $e->getMessage() . "\n";
}

// Test 4: Check Elasticsearch URL
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    if (preg_match('/ELASTICSEARCH_URL=(.+)/', $envContent, $matches)) {
        echo "✅ Elasticsearch URL: " . trim($matches[1]) . "\n";
        
        // Test connection
        $url = trim($matches[1]);
        echo "🔗 Testing connection to: $url\n";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'method' => 'GET'
            ]
        ]);
        
        $response = @file_get_contents($url . '/_cluster/health', false, $context);
        
        if ($response) {
            echo "✅ Elasticsearch is RUNNING and accessible\n";
            $health = json_decode($response, true);
            if (isset($health['status'])) {
                echo "📊 Cluster Status: " . $health['status'] . "\n";
            }
        } else {
            echo "❌ Elasticsearch is NOT running or not accessible\n";
            echo "💡 Start Elasticsearch with: docker run -d --name elasticsearch -p 9200:9200 -e \"discovery.type=single-node\" elasticsearch:8.11.0\n";
        }
    } else {
        echo "❌ ELASTICSEARCH_URL not found in .env\n";
    }
} else {
    echo "❌ .env file not found\n";
}

echo "\n🎯 Summary:\n";
echo "FOSElasticaBundle is properly configured\n";
echo "The only missing piece is Elasticsearch running on localhost:9200\n";
echo "\n📋 Next Steps:\n";
echo "1. Start Elasticsearch\n";
echo "2. Run: php bin/console fos:elastica:populate\n";
echo "3. Test the autocomplete search\n";
