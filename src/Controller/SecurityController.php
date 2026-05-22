<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectAfterLogin();
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // This method can be blank - it will be intercepted by the logout key on your firewall
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectGoogle(ClientRegistry $clientRegistry): Response
    {
        $clientId = (string) $this->getParameter('env(OAUTH_GOOGLE_CLIENT_ID)');
        $clientSecret = (string) $this->getParameter('env(OAUTH_GOOGLE_CLIENT_SECRET)');

        if ($clientId === '' || $clientSecret === '') {
            $this->addFlash('error', 'Google login is not configured yet (missing client_id/client_secret).');
            return $this->redirectToRoute('app_login');
        }

        return $clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile'], []);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheck(): never
    {
        throw new \LogicException('This code should never be reached.');
    }

    private function redirectAfterLogin(): Response
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            $roles = $user->getRoles();
            if (\in_array('ROLE_STAFF', $roles, true) || \in_array('ROLE_ADMIN', $roles, true)) {
                return $this->redirectToRoute('admin_dashboard');
            }
        }

        return $this->redirectToRoute('app_home_page');
    }
}
