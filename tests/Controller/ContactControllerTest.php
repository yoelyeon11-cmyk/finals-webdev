<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ContactControllerTest extends WebTestCase
{
    public function testContactPageLoads(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/contact');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Contact us');
        self::assertCount(1, $crawler->filter('form.contact-form'));
    }

    public function testContactFormSubmitRedirectsWithFlash(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/contact');

        $form = $crawler->selectButton('Send message')->form([
            'contact_form[name]' => 'Test User',
            'contact_form[email]' => 'test@example.com',
            'contact_form[subject]' => 'Order question',
            'contact_form[message]' => 'This is a valid message with enough characters.',
        ]);

        $client->submit($form);

        self::assertResponseRedirects('/contact#contact-form');
        $client->followRedirect();

        self::assertSelectorTextContains('.flash-success', 'Thank you!');
        self::assertSelectorTextContains('body', 'Brevo');
    }
}
