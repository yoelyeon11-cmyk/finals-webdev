<?php

namespace App\Controller\Api\V1;

use App\Entity\User;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
final class AuthController extends AbstractController
{
    #[Route('/me', name: 'api_v1_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json([
                'success' => false,
                'data' => null,
                'error' => ['code' => 'unauthorized', 'message' => 'Authentication required.'],
            ], 401);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName(),
                'roles' => $user->getRoles(),
                'verified' => $user->isVerified(),
            ],
            'error' => null,
        ]);
    }

    #[Route('/login/google', name: 'api_v1_login_google', methods: ['POST'])]
    public function loginWithGoogle(
        Request $request,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtTokenManager,
        UserPasswordHasherInterface $hasher,
        LoggerInterface $logger,
    ): JsonResponse {
        $payload = json_decode((string) $request->getContent(), true);
        $idToken = is_array($payload) ? trim((string) ($payload['idToken'] ?? '')) : '';

        if ($idToken === '') {
            return $this->json([
                'success' => false,
                'data' => null,
                'error' => ['code' => 'validation_error', 'message' => 'idToken is required.'],
            ], 422);
        }

        try {
            $response = HttpClient::create()->request('GET', 'https://oauth2.googleapis.com/tokeninfo', [
                'query' => ['id_token' => $idToken],
                'timeout' => 12,
            ]);
            $googleData = $response->toArray(false);
            $statusCode = $response->getStatusCode();
        } catch (TransportException $e) {
            $logger->error('Google token verification transport error', ['exception' => $e]);
            return $this->json([
                'success' => false,
                'data' => null,
                'error' => ['code' => 'google_unreachable', 'message' => 'Could not verify Google token. Try again.'],
            ], 503);
        } catch (\Throwable $e) {
            $logger->warning('Google token verification failed', ['exception' => $e]);
            return $this->json([
                'success' => false,
                'data' => null,
                'error' => ['code' => 'google_token_invalid', 'message' => 'Invalid Google token.'],
            ], 401);
        }

        if ($statusCode !== 200) {
            return $this->json([
                'success' => false,
                'data' => null,
                'error' => ['code' => 'google_token_invalid', 'message' => 'Invalid Google token.'],
            ], 401);
        }

        $email = trim((string) ($googleData['email'] ?? ''));
        $googleId = trim((string) ($googleData['sub'] ?? ''));
        $emailVerified = (string) ($googleData['email_verified'] ?? '') === 'true';
        $fullName = trim((string) ($googleData['name'] ?? ''));

        if ($email === '' || $googleId === '' || !$emailVerified) {
            return $this->json([
                'success' => false,
                'data' => null,
                'error' => ['code' => 'google_claims_invalid', 'message' => 'Google account must provide a verified email.'],
            ], 401);
        }

        /** @var User|null $user */
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            $baseUsername = strtolower(strstr($email, '@', true) ?: $googleId);
            $username = $baseUsername;
            $suffix = 1;
            while ($em->getRepository(User::class)->findOneBy(['username' => $username])) {
                $username = sprintf('%s_%d', $baseUsername, $suffix++);
            }

            $user = new User();
            $user->setEmail($email);
            $user->setUsername($username);
            $user->setFullName($fullName !== '' ? $fullName : $email);
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($hasher->hashPassword($user, bin2hex(random_bytes(16))));
            $user->setIsVerified(true);
        }

        $user->setGoogleId($googleId);
        if ($fullName !== '') {
            $user->setFullName($fullName);
        }
        $user->setIsVerified(true);

        $em->persist($user);
        $em->flush();

        return $this->json([
            'success' => true,
            'token' => $jwtTokenManager->create($user),
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName(),
                'roles' => $user->getRoles(),
                'verified' => $user->isVerified(),
            ],
        ]);
    }

    #[Route('/register', name: 'api_v1_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        EmailVerificationService $emailVerification,
    ): JsonResponse {
        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json([
                'success' => false,
                'data' => null,
                'error' => ['code' => 'invalid_json', 'message' => 'Invalid JSON body.'],
            ], 400);
        }

        $email = trim((string) ($payload['email'] ?? ''));
        $username = trim((string) ($payload['username'] ?? ''));
        $fullName = trim((string) ($payload['fullName'] ?? ''));
        $plainPassword = (string) ($payload['password'] ?? '');

        if ($email === '' || $username === '' || $fullName === '' || $plainPassword === '') {
            return $this->json([
                'success' => false,
                'data' => null,
                'error' => ['code' => 'validation_error', 'message' => 'email, username, fullName, and password are required.'],
            ], 422);
        }

        $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            return $this->json([
                'success' => false,
                'data' => null,
                'error' => ['code' => 'email_taken', 'message' => 'Email is already registered.'],
            ], 409);
        }

        $existingUsername = $em->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($existingUsername) {
            return $this->json([
                'success' => false,
                'data' => null,
                'error' => ['code' => 'username_taken', 'message' => 'Username is already taken.'],
            ], 409);
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

        return $this->json([
            'success' => true,
            'data' => [
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'verified' => $user->isVerified(),
                'message' => 'Account created. Please verify your email.',
            ],
            'error' => null,
        ], 201);
    }
}

