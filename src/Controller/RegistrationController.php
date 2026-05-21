<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        EmailVerificationService $emailVerification,
    ): Response {
        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email'));
            $username = trim((string) $request->request->get('username'));
            $fullName = trim((string) $request->request->get('fullName'));
            $plainPassword = (string) $request->request->get('password');

            if ($email === '' || $username === '' || $fullName === '' || $plainPassword === '') {
                $this->addFlash('error', 'Please fill in all fields.');
                return $this->redirectToRoute('app_register');
            }

            $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existing) {
                $this->addFlash('error', 'Email is already registered.');
                return $this->redirectToRoute('app_register');
            }

            $existingUsername = $em->getRepository(User::class)->findOneBy(['username' => $username]);
            if ($existingUsername) {
                $this->addFlash('error', 'Username is already taken.');
                return $this->redirectToRoute('app_register');
            }

            $user = new User();
            $user->setEmail($email);
            $user->setUsername($username);
            $user->setFullName($fullName);
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($hasher->hashPassword($user, $plainPassword));

            $emailVerification->issueToken($user);
            $em->persist($user);
            $em->flush();
            $emailVerification->sendVerificationEmail($user);

            $this->addFlash('success', 'Account created. Please check your email to verify your account.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig');
    }
}

