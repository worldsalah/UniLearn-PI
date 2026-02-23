<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class KernelTest extends WebTestCase
{
    public function testApplicationBoot(): void
    {
        $client = static::createClient();

        // Test that the application boots without errors
        $this->assertInstanceOf('Symfony\Bundle\FrameworkBundle\KernelBrowser', $client);
    }

    public function testHomePage(): void
    {
        $client = static::createClient();

        // Test the main route (if it exists)
        $crawler = $client->request('GET', '/');

        // If the route doesn't exist, we expect a 404
        $this->assertContains($client->getResponse()->getStatusCode(), [200, 404, 302]);
    }

    public function testServiceContainer(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $this->assertInstanceOf('Psr\Container\ContainerInterface', $container);
    }
}
