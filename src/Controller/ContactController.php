<?php

namespace App\Controller;

use App\Form\ContactFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function __invoke(
        Request $request,
        MailerInterface $mailer,
        #[Autowire('%env(CONTACT_INBOX)%')]
        string $contactInbox,
        #[Autowire('%env(MAILER_DEFAULT_FROM)%')]
        string $mailFrom,
    ): Response {
        $form = $this->createForm(ContactFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{name: string, email: string, subject: ?string, message: string} $data */
            $data = $form->getData();

            $subjectLine = trim((string) ($data['subject'] ?? ''));
            $emailSubject = $subjectLine !== ''
                ? '[Cloudrobe] ' . $subjectLine
                : '[Cloudrobe] Contact form message';

            $plain = sprintf(
                "Name: %s\nEmail: %s\n\n%s",
                $data['name'],
                $data['email'],
                $data['message'],
            );

            $safeName = htmlspecialchars($data['name'], \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
            $safeEmail = htmlspecialchars($data['email'], \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
            $safeMessage = nl2br(htmlspecialchars($data['message'], \ENT_QUOTES | \ENT_HTML5, 'UTF-8'));

            $html = <<<HTML
                <p><strong>Name:</strong> {$safeName}<br>
                <strong>Email:</strong> {$safeEmail}</p>
                <p><strong>Message:</strong></p>
                <p>{$safeMessage}</p>
                HTML;

            $email = (new Email())
                ->from(Address::create($mailFrom))
                ->to($contactInbox)
                ->replyTo(new Address($data['email'], $data['name']))
                ->subject($emailSubject)
                ->text($plain)
                ->html($html);

            try {
                $mailer->send($email);
            } catch (TransportExceptionInterface) {
                $this->addFlash('danger', 'Delivery failed through Brevo right now. Please try again in a few minutes or email us directly.');

                return $this->render('contact/index.html.twig', [
                    'form' => $form,
                    'contact_inbox' => $contactInbox,
                ]);
            }

            $this->addFlash('success', 'Thank you! Your message was sent through Brevo. We received your inquiry and will reply within one business day.');

            return $this->redirect($this->generateUrl('app_contact') . '#contact-form');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Please complete all required fields correctly before sending.');
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form,
            'contact_inbox' => $contactInbox,
        ]);
    }
}
