<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class EmailVerificationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $defaultFrom,
    ) {
    }

    public function issueToken(User $user, \DateTimeImmutable $expiresAt = new \DateTimeImmutable('+1 day')): void
    {
        $token = bin2hex(random_bytes(32));
        $user->setEmailVerificationToken($token);
        $user->setEmailVerificationTokenExpiresAt($expiresAt);
        $user->setIsVerified(false);
    }

    public function sendVerificationEmail(User $user): void
    {
        $token = $user->getEmailVerificationToken();
        if ($token === null) {
            throw new \RuntimeException('No verification token present for user.');
        }

        $verifyUrl = $this->urlGenerator->generate(
            'email_verify',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new Email())
            ->from($this->defaultFrom)
            ->to((string) $user->getEmail())
            ->subject('Verify your email')
            ->html(sprintf(
                '<p>Hi %s,</p><p>Please verify your email by clicking this link:</p><p><a href="%s">%s</a></p><p>This link expires in 24 hours.</p>',
                htmlspecialchars((string) $user->getFullName(), ENT_QUOTES),
                htmlspecialchars($verifyUrl, ENT_QUOTES),
                htmlspecialchars($verifyUrl, ENT_QUOTES)
            ));

        $this->mailer->send($email);
    }

    public function verifyByToken(string $token): ?User
    {
        $repo = $this->em->getRepository(User::class);
        /** @var User|null $user */
        $user = $repo->findOneBy(['emailVerificationToken' => $token]);

        if (!$user instanceof User) {
            return null;
        }

        if (!$user->isEmailVerificationTokenValid($token)) {
            return null;
        }

        $user->setIsVerified(true);
        $user->setEmailVerificationToken(null);
        $user->setEmailVerificationTokenExpiresAt(null);
        $this->em->flush();

        return $user;
    }
}

