<?php
// src/Controller/AdminDestinationController.php
namespace App\Controller;

use App\Entity\ActivityLog;
use App\Entity\Destination;
use App\Form\DestinationType;
use App\Repository\DestinationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/destinations', name: 'admin_destinations_')]
#[IsGranted('ROLE_STAFF')] // staff (and admin via hierarchy) can access
class AdminDestinationController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(DestinationRepository $repo): Response
    {
        $user = $this->getUser();
        
        // Both staff and admin have the same access - can see all destinations
        $destinations = $repo->findAll();
        
        return $this->render('admin/destinations/index.html.twig', [
            'page_title' => 'Manage Destinations',
            'destinations' => $destinations,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $destination = new Destination();
        // Set the creator for all new destinations
        $destination->setCreatedBy($this->getUser());
        
        $form = $this->createForm(DestinationType::class, $destination);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($destination);
            $em->flush();
            
            // Log the activity
            $this->logActivity('CREATE', 'Destination', $destination->getId(), $destination->getName(), 
                "Created destination: {$destination->getName()} in {$destination->getLocation()}");
            
            $this->addFlash('success', 'Destination created.');
            return $this->redirectToRoute('admin_destinations_index');
        }

        return $this->render('admin/destinations/new.html.twig', [
            'page_title' => 'Add Destination',
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Destination $destination): Response
    {
        // Both staff and admin have full access - no restrictions

        return $this->render('admin/destinations/show.html.twig', [
            'page_title' => 'View Destination',
            'destination' => $destination,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET','POST'])]
    public function edit(Request $request, Destination $destination, EntityManagerInterface $em): Response
    {
        // Both staff and admin have full access - no restrictions

        $form = $this->createForm(DestinationType::class, $destination);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            
            // Log the activity
            $this->logActivity('UPDATE', 'Destination', $destination->getId(), $destination->getName(), 
                "Updated destination: {$destination->getName()}");
            
            $this->addFlash('success', 'Destination updated.');
            return $this->redirectToRoute('admin_destinations_index');
        }

        return $this->render('admin/destinations/edit.html.twig', [
            'page_title' => 'Edit Destination',
            'form' => $form,
            'destination' => $destination,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Destination $destination, EntityManagerInterface $em): Response
    {
        // Both staff and admin have full access - no restrictions

        // Use the same method as StaffDestinationsController for consistency
        if ($this->isCsrfTokenValid('delete'.$destination->getId(), $request->request->get('_token'))) {
            $destinationName = $destination->getName();
            $destinationId = $destination->getId();
            
            $em->remove($destination);
            $em->flush();
            
            // Log the activity
            $this->logActivity('DELETE', 'Destination', $destinationId, $destinationName, 
                "Deleted destination: {$destinationName}");
            
            $this->addFlash('success', 'Destination deleted.');
        }
        return $this->redirectToRoute('admin_destinations_index');
    }

    /**
     * Helper method to log activities
     */
    private function logActivity(string $action, string $entityType, ?int $entityId, ?string $entityName, string $description): void
    {
        $user = $this->getUser();
        $request = $this->requestStack->getCurrentRequest();
        
        $activityLog = ActivityLog::create(
            action: $action,
            entityType: $entityType,
            entityId: $entityId,
            entityName: $entityName,
            user: $user,
            description: $description
        );

        if ($request) {
            $activityLog->setIpAddress($request->getClientIp())
                       ->setUserAgent($request->headers->get('User-Agent'));
        }

        $this->entityManager->persist($activityLog);
        $this->entityManager->flush();
    }
}
