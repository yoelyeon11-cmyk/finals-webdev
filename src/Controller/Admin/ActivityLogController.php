<?php

namespace App\Controller\Admin;

use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/activity-logs')]
#[IsGranted('ROLE_ADMIN')]
class ActivityLogController extends AbstractController
{
    #[Route('/', name: 'admin_activity_logs')]
    public function index(Request $request, ActivityLogRepository $repository): Response
    {
        $filters = [
            'username' => $request->query->get('username', ''),
            'action' => $request->query->get('action', ''),
            'role' => $request->query->get('role', ''),
            'dateFrom' => $request->query->get('dateFrom') ? new \DateTime($request->query->get('dateFrom')) : null,
            'dateTo' => $request->query->get('dateTo') ? new \DateTime($request->query->get('dateTo')) : null,
        ];

        $logs = $repository->findWithFilters($filters, 500);
        $statistics = $repository->getStatistics();

        return $this->render('admin/activity_log/index.html.twig', [
            'logs' => $logs,
            'statistics' => $statistics,
            'filters' => $filters,
        ]);
    }
}
