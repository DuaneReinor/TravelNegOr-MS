<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/activity-logs', name: 'admin_activity_logs_')]
#[IsGranted('ROLE_ADMIN')]
class AdminActivityLogController extends AbstractController
{
    public function __construct(
        private ActivityLogRepository $activityLogRepository
    ) {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $search = $request->query->get('search', '');
        $action = $request->query->get('action', '');
        $entityType = $request->query->get('entity_type', '');

        // Build query based on filters
        $qb = $this->activityLogRepository->createQueryBuilder('al')
            ->leftJoin('al.user', 'u')
            ->addSelect('u')
            ->orderBy('al.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($search) {
            $qb->andWhere('al.action LIKE :search OR al.entityType LIKE :search OR al.description LIKE :search OR al.entityName LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($action) {
            $qb->andWhere('al.action = :action')
               ->setParameter('action', $action);
        }

        if ($entityType) {
            $qb->andWhere('al.entityType = :entityType')
               ->setParameter('entityType', $entityType);
        }

        $activityLogs = $qb->getQuery()->getResult();

        // Get total count for pagination
        $totalCount = count($activityLogs);
        $totalPages = ceil($totalCount / $limit);

        // Get statistics
        $stats = $this->activityLogRepository->getActivityStats();

        // Get filter options - including new security actions
        $actions = ['LOGIN', 'LOGOUT', 'LOGIN_FAILED', 'CREATE', 'UPDATE', 'DELETE'];
        $entityTypes = ['Destination', 'Hotel', 'User'];

        return $this->render('admin/activity_logs/index.html.twig', [
            'page_title' => 'Activity Logs',
            'activity_logs' => $activityLogs,
            'stats' => $stats,
            'actions' => $actions,
            'entity_types' => $entityTypes,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'search' => $search,
            'current_action' => $action,
            'current_entity_type' => $entityType,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $activityLog = $this->activityLogRepository->find($id);

        if (!$activityLog) {
            throw $this->createNotFoundException('Activity log not found');
        }

        return $this->render('admin/activity_logs/show.html.twig', [
            'page_title' => 'Activity Log Details',
            'activity_log' => $activityLog,
        ]);
    }

    #[Route('/entity/{entityType}/{entityId}', name: 'entity_logs', methods: ['GET'])]
    public function entityLogs(string $entityType, int $entityId): Response
    {
        $activityLogs = $this->activityLogRepository->getEntityActivity($entityType, $entityId);

        return $this->render('admin/activity_logs/entity_logs.html.twig', [
            'page_title' => ucfirst($entityType) . ' Activity History',
            'activity_logs' => $activityLogs,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);
    }

    #[Route('/clean', name: 'clean', methods: ['POST'])]
    public function clean(Request $request): Response
    {
        $daysToKeep = $request->request->getInt('days_to_keep', 90);

        if ($this->isCsrfTokenValid('clean_activity_logs', $request->request->get('_token'))) {
            $deletedCount = $this->activityLogRepository->cleanOldLogs($daysToKeep);
            $this->addFlash('success', "Cleaned up {$deletedCount} old activity logs (older than {$daysToKeep} days).");
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_activity_logs_index');
    }
}