<?php

namespace App\Controller;

use App\Service\EmailVerificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EmailVerificationController extends AbstractController
{
    #[Route('/verify-email', name: 'email_verify', methods: ['GET'])]
    public function verify(Request $request, EmailVerificationService $emailVerification): Response
    {
        $token = (string) $request->query->get('token', '');
        $user = $token !== '' ? $emailVerification->verifyByToken($token) : null;

        $wantsJson = str_contains((string) $request->headers->get('Accept', ''), 'application/json')
            || str_starts_with((string) $request->getPathInfo(), '/api/');

        if ($wantsJson) {
            if ($user) {
                return new JsonResponse([
                    'success' => true,
                    'data' => [
                        'email' => $user->getEmail(),
                        'verified' => $user->isVerified(),
                    ],
                    'error' => null,
                ]);
            }

            return new JsonResponse([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'invalid_or_expired_token',
                    'message' => 'Verification link is invalid or has expired.',
                ],
            ], 400);
        }

        if ($user) {
            return $this->render('security/verify_result.html.twig', [
                'success' => true,
            ]);
        }

        return $this->render('security/verify_result.html.twig', [
            'success' => false,
        ]);
    }

    #[Route('/api/v1/verify-email', name: 'api_email_verify', methods: ['GET'])]
    public function verifyApi(Request $request, EmailVerificationService $emailVerification): Response
    {
        // Reuse the same verification logic; return JSON consistently.
        $token = (string) $request->query->get('token', '');
        $user = $token !== '' ? $emailVerification->verifyByToken($token) : null;

        if ($user) {
            return new JsonResponse([
                'success' => true,
                'data' => [
                    'email' => $user->getEmail(),
                    'verified' => $user->isVerified(),
                ],
                'error' => null,
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => 'invalid_or_expired_token',
                'message' => 'Verification link is invalid or has expired.',
            ],
        ], 400);
    }
}

