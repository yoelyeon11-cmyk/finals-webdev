<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * Staff → admin dashboard; customers → public home (avoids 403 on /admin).
 */
final class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
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
}
