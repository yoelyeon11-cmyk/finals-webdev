<?php

namespace App\Controller\Admin;

use App\Entity\CustomCosplayRequest;
use App\Repository\CustomCosplayRequestRepository;
use App\Service\ActivityLogger;
use App\Service\AdminRealtimeHelper;
use App\Service\RealtimeBroadcastClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/verification')]
#[IsGranted('ROLE_STAFF')]
class VerificationController extends AbstractController
{
    public function __construct(
        private readonly AdminRealtimeHelper $realtime,
    ) {
    }

    #[Route('/', name: 'admin_verification_index')]
    public function index(CustomCosplayRequestRepository $repository): Response
    {
        $pendingRequests = $repository->createQueryBuilder('r')
            ->where('r.status IN (:statuses)')
            ->setParameter('statuses', ['new_request', 'awaiting_approval', 'quote_sent'])
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/verification/index.html.twig', [
            'pendingRequests' => $pendingRequests,
            'verificationFingerprint' => $this->realtime->verificationFingerprint($repository),
            'websocketUrl' => $this->realtime->websocketUrl(),
        ]);
    }

    #[Route('/{id}/review', name: 'admin_verification_review')]
    public function review(CustomCosplayRequest $customRequest): Response
    {
        return $this->render('admin/verification/review.html.twig', [
            'request' => $customRequest,
        ]);
    }

    #[Route('/{id}/approve', name: 'admin_verification_approve', methods: ['POST'])]
    public function approve(
        Request $request,
        CustomCosplayRequest $customRequest,
        EntityManagerInterface $em,
        ActivityLogger $logger,
        RealtimeBroadcastClient $realtimeBroadcast,
    ): Response {
        if (!$this->isCsrfTokenValid('approve_' . $customRequest->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('admin_verification_review', ['id' => $customRequest->getId()]);
        }

        $finalPrice = $request->request->get('final_price');
        if ($finalPrice) {
            $customRequest->setEstimatedCost($finalPrice);
        }

        $customRequest->setStatus('approved');
        $customRequest->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        $realtimeBroadcast->publish('custom_request.updated', [
            'requestId' => $customRequest->getId(),
            'status' => $customRequest->getStatus(),
        ]);
        $realtimeBroadcast->publish('verification.updated', [
            'requestId' => $customRequest->getId(),
        ]);

        $logger->log('Request Approved', 'Approved custom request ID: ' . $customRequest->getId() . ' for ' . $customRequest->getCustomerName() . ' - ₱' . $customRequest->getEstimatedCost());
        $this->addFlash('success', 'Request approved! Customer can now proceed with order.');

        return $this->redirectToRoute('admin_verification_index');
    }

    #[Route('/{id}/reject', name: 'admin_verification_reject', methods: ['POST'])]
    public function reject(
        Request $request,
        CustomCosplayRequest $customRequest,
        EntityManagerInterface $em,
        ActivityLogger $logger,
        RealtimeBroadcastClient $realtimeBroadcast,
    ): Response {
        if (!$this->isCsrfTokenValid('reject_' . $customRequest->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('admin_verification_review', ['id' => $customRequest->getId()]);
        }

        $customRequest->setStatus('rejected');
        $customRequest->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        $realtimeBroadcast->publish('custom_request.updated', [
            'requestId' => $customRequest->getId(),
            'status' => $customRequest->getStatus(),
        ]);
        $realtimeBroadcast->publish('verification.updated', [
            'requestId' => $customRequest->getId(),
        ]);

        $logger->log('Request Rejected', 'Rejected custom request ID: ' . $customRequest->getId() . ' for ' . $customRequest->getCustomerName());
        $this->addFlash('warning', 'Request has been rejected.');

        return $this->redirectToRoute('admin_verification_index');
    }
}
