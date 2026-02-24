<?php

require_once 'vendor/autoload.php';

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

$kernel = new Kernel('dev', true);
$kernel->boot();

$request = Request::create('/api/marketplace/trending');
$response = $kernel->handle($request);

echo "Status Code: " . $response->getStatusCode() . "\n";
echo "Content Type: " . $response->headers->get('Content-Type') . "\n";
echo "Content:\n";
echo $response->getContent() . "\n";

$kernel->terminate($request, $response);
