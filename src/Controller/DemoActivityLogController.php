<?php

namespace App\Controller;

use App\Entity\ActivityLog;
use App\Entity\Destination;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/demo')]
class DemoActivityLogController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/activity-log-test', name: 'demo_activity_log_test')]
    #[IsGranted('ROLE_ADMIN')]
    public function testActivityLog(): Response
    {
        $user = $this->getUser();
        
        // Create a test destination
        $destination = new Destination();
        $destination->setName('Demo Activity Log Test Destination')
                   ->setLocation('Demo Location')
                   ->setDescription('This is a demonstration of the activity logging system')
                   ->setImage('demo-image.jpg')
                   ->setCreatedBy($user);

        $this->entityManager->persist($destination);
        $this->entityManager->flush();

        // Update the destination
        $destination->setDescription('Updated description for demo');
        $this->entityManager->flush();

        // Get recent activity logs
        $recentLogs = $this->entityManager->getRepository(ActivityLog::class)
            ->getRecentActivity(10);

        return $this->render('demo/activity_log_test.html.twig', [
            'destination' => $destination,
            'recent_logs' => $recentLogs,
            'total_logs' => count($recentLogs),
        ]);
    }
}