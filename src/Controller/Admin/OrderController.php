<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Entity\CustomCosplayRequest;
use App\Form\OrderType;
use App\Form\OrderStatusType;
use App\Repository\OrderRepository;
use App\Repository\CustomCosplayRequestRepository;
use App\Repository\ProductRepository;
use App\Service\ActivityLogger;
use App\Service\OrderRealtimeEventStore;
use App\Service\OrderStatusPushNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/order')]
#[IsGranted('ROLE_STAFF')]
class OrderController extends AbstractController
{
    // Order Status - View and track existing orders
    #[Route('/status', name: 'admin_order_status')]
    #[Route('/', name: 'admin_order_index')]
    public function index(Request $request, OrderRepository $repository): Response
    {
        $searchTerm = $request->query->get('search', '');
        
        if ($searchTerm) {
            $orders = $repository->findBySearchTerm($searchTerm);
        } else {
            $orders = $repository->findBy([], ['orderDate' => 'DESC']);
        }

        $latestOrder = $repository->findOneBy([], ['id' => 'DESC']);

        return $this->render('admin/order/index.html.twig', [
            'orders' => $orders,
            'searchTerm' => $searchTerm,
            'latestOrderId' => $latestOrder?->getId(),
        ]);
    }

    // Place Order - Create new orders or from approved requests
    #[Route('/place', name: 'admin_order_place', methods: ['GET', 'POST'])]
    public function place(
        Request $request,
        CustomCosplayRequestRepository $requestRepo, 
        ProductRepository $productRepo,
        EntityManagerInterface $em,
        ActivityLogger $logger
    ): Response
    {
        // Handle form submission
        if ($request->isMethod('POST')) {
            $orderData = $request->request->all('order');
            
            $order = new Order();
            $order->setCustomerName($orderData['customerName'] ?? '');
            $order->setCustomerEmail($orderData['customerEmail'] ?? '');
            $order->setCustomerPhone($orderData['customerPhone'] ?? '');
            $order->setItemsDescription($orderData['itemsDescription'] ?? '');
            
            // Ensure totalAmount is a valid decimal
            $totalAmount = $orderData['totalAmount'] ?? '0.00';
            if (empty($totalAmount) || !is_numeric($totalAmount)) {
                $totalAmount = '0.00';
            }
            $order->setTotalAmount($totalAmount);
            
            $order->setPaymentMethod(!empty($orderData['paymentMethod']) ? $orderData['paymentMethod'] : null);
            $order->setShippingAddress($orderData['shippingAddress'] ?? null);
            $order->setCreatedBy($this->getUser());

            $em->persist($order);
            $em->flush();

            $logger->log('Order Created', 'Created order ' . $order->getTransactionId() . ' for ' . $order->getCustomerName() . ' - ₱' . $order->getTotalAmount());
            $this->addFlash('success', 'Order created successfully! Transaction ID: ' . $order->getTransactionId());
            
            return $this->redirectToRoute('admin_order_status');
        }

        // Get approved requests that haven't been converted to orders yet
        $approvedRequests = $requestRepo->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', 'approved')
            ->orderBy('r.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();

        // Get all available products
        $products = $productRepo->findAll();

        return $this->render('admin/order/place.html.twig', [
            'approvedRequests' => $approvedRequests,
            'products' => $products,
        ]);
    }

    #[Route('/new', name: 'admin_order_new')]
    public function new(Request $request, EntityManagerInterface $em, ActivityLogger $logger): Response
    {
        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->setCreatedBy($this->getUser());
            $em->persist($order);
            $em->flush();

            $logger->log('Order Created', 'Created order ' . $order->getTransactionId() . ' for ' . $order->getCustomerName() . ' - ₱' . $order->getTotalAmount());
            $this->addFlash('success', 'Order created successfully! Transaction ID: ' . $order->getTransactionId());
            return $this->redirectToRoute('admin_order_status');
        }

        return $this->render('admin/order/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/create-from-request/{requestId}', name: 'admin_order_create_from_request')]
    public function createFromRequest(
        int $requestId,
        Request $request,
        CustomCosplayRequestRepository $requestRepo,
        EntityManagerInterface $em,
        ActivityLogger $logger
    ): Response {
        $customRequest = $requestRepo->find($requestId);
        
        if (!$customRequest) {
            throw $this->createNotFoundException('Custom request not found');
        }

        // Pre-fill order with request data
        $order = new Order();
        $order->setCustomerName($customRequest->getCustomerName());
        $order->setCustomerEmail($customRequest->getCustomerEmail());
        $order->setCustomerPhone($customRequest->getCustomerPhone());
        $order->setItemsDescription('Custom Cosplay: ' . $customRequest->getCosplayCharacter());
        $order->setTotalAmount($customRequest->getEstimatedCost() ?? '0.00');
        $order->setCustomRequest($customRequest);

        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->setCreatedBy($this->getUser());
            $customRequest->setStatus('converted_to_order');
            $customRequest->setUpdatedAt(new \DateTimeImmutable());
            
            $em->persist($order);
            $em->flush();

            $logger->log('Order Created from Request', 'Converted custom request ID: ' . $customRequest->getId() . ' to order ' . $order->getTransactionId() . ' - ₱' . $order->getTotalAmount());
            $this->addFlash('success', 'Order created from custom request! Transaction ID: ' . $order->getTransactionId());
            return $this->redirectToRoute('admin_order_status');
        }

        return $this->render('admin/order/new.html.twig', [
            'form' => $form,
            'customRequest' => $customRequest,
        ]);
    }

    #[Route('/{id}', name: 'admin_order_show')]
    public function show(Order $order): Response
    {
        return $this->render('admin/order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_order_edit')]
    public function edit(Request $request, Order $order, EntityManagerInterface $em): Response
    {
        if ($order->getStatus() !== 'new_order') {
            $this->addFlash('error', 'This order can no longer be edited because it is already in progress.');
            return $this->redirectToRoute('admin_order_show', ['id' => $order->getId()]);
        }

        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();

            $this->addFlash('success', 'Order updated successfully!');
            return $this->redirectToRoute('admin_order_show', ['id' => $order->getId()]);
        }

        return $this->render('admin/order/edit.html.twig', [
            'form' => $form,
            'order' => $order,
        ]);
    }

    #[Route('/{id}/update-status', name: 'admin_order_update_status', methods: ['POST'])]
    public function updateStatus(
        Request $request,
        Order $order,
        EntityManagerInterface $em,
        ActivityLogger $logger,
        OrderStatusPushNotifier $pushNotifier,
        OrderRealtimeEventStore $realtimeEventStore,
    ): Response {
        $token = $request->request->get('_token');
        
        if (!$this->isCsrfTokenValid('order_status_' . $order->getId(), $token)) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('admin_order_show', ['id' => $order->getId()]);
        }

        $orderData = $request->request->all('order');
        $oldStatus = $order->getStatus();

        // Once delivered, the order is locked.
        if ($oldStatus === 'delivered') {
            $this->addFlash('error', 'Delivered orders are locked and cannot be updated.');
            return $this->redirectToRoute('admin_order_show', ['id' => $order->getId()]);
        }
        
        if (isset($orderData['status'])) {
            $newStatus = (string) $orderData['status'];

            if (!$order->canTransitionTo($newStatus)) {
                $this->addFlash('error', 'Invalid status change. You can only move the status forward until it reaches Delivered.');
                return $this->redirectToRoute('admin_order_show', ['id' => $order->getId()]);
            }

            $order->setStatus($newStatus);
        }
        
        if (isset($orderData['shippingCarrier'])) {
            $order->setShippingCarrier($orderData['shippingCarrier']);
        }
        
        if (isset($orderData['trackingNumber'])) {
            $order->setTrackingNumber($orderData['trackingNumber']);
        }
        
        $em->flush();

        $pushNotifier->notifyIfStatusChanged($order, $oldStatus);
        $realtimeEventStore->publishStatusChanged($order, $oldStatus);

        $logger->log('Order Status Updated', 'Updated order ' . $order->getTransactionId() . ' status from "' . $oldStatus . '" to "' . $order->getStatus() . '"');
        $this->addFlash('success', 'Order status updated to: ' . $order->getStatusLabel());
        return $this->redirectToRoute('admin_order_show', ['id' => $order->getId()]);
    }
}
