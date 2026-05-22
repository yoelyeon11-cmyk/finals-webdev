<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class GoogleAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return 'connect_google_check' === $request->attributes->get('_route');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        /** @var GoogleUser $googleUser */
        $googleUser = $client->fetchUserFromToken($accessToken);
        $email = (string) $googleUser->getEmail();
        $googleId = (string) $googleUser->getId();

        if ('' === $email) {
            throw new CustomUserMessageAuthenticationException('Google account did not return an email address.');
        }

        return new SelfValidatingPassport(
            new UserBadge($email, function () use ($email, $googleId, $googleUser): User {
                $user = $this->userRepository->findOneBy(['email' => $email]);

                if (!$user instanceof User) {
                    $user = new User();
                    $user->setEmail($email);
                    $user->setUsername($this->generateUsernameFromEmail($email));
                    $user->setFullName($googleUser->getName() ?: $email);
                    $user->setRoles(['ROLE_STAFF']);
                    $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(32))));
                }

                $roles = $user->getRoles();
                if (!\in_array('ROLE_STAFF', $roles, true) && !\in_array('ROLE_ADMIN', $roles, true)) {
                    throw new CustomUserMessageAuthenticationException('Google login is only available for staff accounts.');
                }

                $user->setGoogleId($googleId);
                $user->setIsVerified(true);

                if (!$user->getFullName()) {
                    $user->setFullName($googleUser->getName() ?: $email);
                }

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if ($user instanceof User) {
            $roles = $user->getRoles();
            if (\in_array('ROLE_STAFF', $roles, true) || \in_array('ROLE_ADMIN', $roles, true)) {
                return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
            }
        }

        return new RedirectResponse($this->urlGenerator->generate('app_home_page'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

    private function generateUsernameFromEmail(string $email): string
    {
        $base = strtolower((string) preg_replace('/[^a-zA-Z0-9_]/', '', (string) strstr($email, '@', true)));
        $base = '' !== $base ? $base : 'staff';
        $username = $base;
        $i = 1;

        while ($this->userRepository->findOneBy(['username' => $username]) instanceof User) {
            ++$i;
            $username = sprintf('%s%d', $base, $i);
        }

        return $username;
    }
}
