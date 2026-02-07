<?php

require __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

$kernel = new Kernel('dev', true);
$request = Request::create('/marketplace/?q=tutoring&category=Tutoring&minPrice=10&maxPrice=100&sortBy=az', 'GET');

try {
    $response = $kernel->handle($request);
    echo "Status: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() === 200) {
        $content = $response->getContent();
        
        // Check if products are displayed
        if (strpos($content, 'No services found') !== false) {
            echo "ERROR: No services found message displayed\n";
        } elseif (preg_match_all('/<div class="col-md-6 col-lg-4">/', $content, $matches)) {
            echo "SUCCESS: Found " . count($matches[0]) . " product cards\n";
        } else {
            echo "WARNING: Could not detect product cards\n";
        }
        
        // Check if search form is present
        if (strpos($content, 'name="q"') !== false) {
            echo "SUCCESS: Search input found\n";
        } else {
            echo "ERROR: Search input missing\n";
        }
        
        // Check if category dropdown is present
        if (strpos($content, 'name="category"') !== false) {
            echo "SUCCESS: Category filter found\n";
        } else {
            echo "ERROR: Category filter missing\n";
        }
        
        // Check if price inputs are present
        if (strpos($content, 'name="minPrice"') !== false && strpos($content, 'name="maxPrice"') !== false) {
            echo "SUCCESS: Price range filters found\n";
        } else {
            echo "ERROR: Price range filters missing\n";
        }
        
        // Check if sort dropdown is present
        if (strpos($content, 'name="sortBy"') !== false) {
            echo "SUCCESS: Sort dropdown found\n";
        } else {
            echo "ERROR: Sort dropdown missing\n";
        }
    } else {
        echo "ERROR: Page returned status " . $response->getStatusCode() . "\n";
        echo substr($response->getContent(), 0, 500) . "\n";
    }
    
    $kernel->terminate($request, $response);
} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
