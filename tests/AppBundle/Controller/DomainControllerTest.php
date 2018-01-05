<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DomainControllerTest extends WebTestCase
{
    public function testDomain()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/domain/1');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('Domain 1', $crawler->filter('h1')->text());
    }
}
