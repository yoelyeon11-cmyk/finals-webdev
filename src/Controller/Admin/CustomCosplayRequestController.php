<?php

namespace App\Controller\Admin;

use App\Entity\CustomCosplayRequest;
use App\Form\CustomCosplayRequestType;
use App\Repository\CustomCosplayRequestRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/custom-request')]
#[IsGranted('ROLE_STAFF')]
class CustomCosplayRequestController extends AbstractController
{
    #[Route('/', name: 'admin_custom_request_index')]
    public function index(CustomCosplayRequestRepository $repository): Response
    {
        $requests = $repository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/custom_request/index.html.twig', [
            'requests' => $requests,
        ]);
    }

    #[Route('/new', name: 'admin_custom_request_new')]
    public function new(Request $request, EntityManagerInterface $em, ActivityLogger $logger): Response
    {
        $customRequest = new CustomCosplayRequest();
        $form = $this->createForm(CustomCosplayRequestType::class, $customRequest, [
            'include_status' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $customRequest->setCreatedBy($this->getUser());
            $em->persist($customRequest);
            $em->flush();

            $logger->log('Custom Request Created', 'Created custom request for: ' . $customRequest->getCustomerName() . ' - ' . $customRequest->getCosplayCharacter());
            $this->addFlash('success', 'Custom cosplay request created successfully! It will appear in Verification for review.');
            return $this->redirectToRoute('admin_verification_index');
        }

        return $this->render('admin/custom_request/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_custom_request_show')]
    public function show(CustomCosplayRequest $customRequest): Response
    {
        return $this->render('admin/custom_request/show.html.twig', [
            'request' => $customRequest,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_custom_request_edit')]
    public function edit(Request $request, CustomCosplayRequest $customRequest, EntityManagerInterface $em, ActivityLogger $logger): Response
    {
        $form = $this->createForm(CustomCosplayRequestType::class, $customRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $customRequest->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();

            $logger->log('Custom Request Updated', 'Updated custom request ID: ' . $customRequest->getId() . ' for ' . $customRequest->getCustomerName());
            $this->addFlash('success', 'Custom cosplay request updated successfully!');
            return $this->redirectToRoute('admin_custom_request_show', ['id' => $customRequest->getId()]);
        }

        return $this->render('admin/custom_request/edit.html.twig', [
            'form' => $form,
            'request' => $customRequest,
        ]);
    }

    #[Route('/{id}/verify', name: 'admin_custom_request_verify', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function verify(CustomCosplayRequest $customRequest, EntityManagerInterface $em): Response
    {
        $customRequest->setStatus('approved');
        $customRequest->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', 'Request verified and approved!');
        return $this->redirectToRoute('admin_custom_request_show', ['id' => $customRequest->getId()]);
    }

    #[Route('/{id}/convert-to-order', name: 'admin_custom_request_convert')]
    public function convertToOrder(CustomCosplayRequest $customRequest): Response
    {
        // Redirect to order creation with pre-filled data
        return $this->redirectToRoute('admin_order_create_from_request', ['requestId' => $customRequest->getId()]);
    }
}
