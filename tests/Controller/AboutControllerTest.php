<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AboutControllerTest extends WebTestCase
{
    public function testAboutPageIsSuccessfulAndContainsTeam(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/about');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h2#team-heading', 'Meet the team');
        self::assertSelectorTextContains('body', 'Yaninna Grace Alanunay');
        self::assertSelectorTextContains('body', 'Founder & Creative Director');
    }
}
