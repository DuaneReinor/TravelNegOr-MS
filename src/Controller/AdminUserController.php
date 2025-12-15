<?php

namespace App\Controller;

use App\Entity\ActivityLog;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users', name: 'admin_users_')]
#[IsGranted('ROLE_STAFF')] // staff (and admin via hierarchy) can access
class AdminUserController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                // Log the activity with error handling
                try {
                    $this->logActivity('CREATE', 'User', $user->getId(), $user->getEmail(), 
                        "Created user: {$user->getFirstName()} {$user->getLastName()}");
                } catch (\Exception $e) {
                    // Don't fail the entire operation if logging fails
                    error_log('Activity logging failed: ' . $e->getMessage());
                }

                $this->addFlash('success', 'User created successfully!');
                return $this->redirectToRoute('admin_users_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to create user: ' . $e->getMessage());
            }
        }

        return $this->render('admin/users/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->flush();

                // Log the activity with error handling
                try {
                    $this->logActivity('UPDATE', 'User', $user->getId(), $user->getEmail(), 
                        "Updated user: {$user->getFirstName()} {$user->getLastName()}");
                } catch (\Exception $e) {
                    error_log('Activity logging failed: ' . $e->getMessage());
                }

                $this->addFlash('success', 'User updated successfully!');
                return $this->redirectToRoute('admin_users_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to update user: ' . $e->getMessage());
            }
        }

        return $this->render('admin/users/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->getPayload()->getString('_token'))) {
            try {
                $userEmail = $user->getEmail();
                $userId = $user->getId();
                
                $this->entityManager->remove($user);
                $this->entityManager->flush();
                
                // Log the activity with error handling
                try {
                    $this->logActivity('DELETE', 'User', $userId, $userEmail, 
                        "Deleted user: {$userEmail}");
                } catch (\Exception $e) {
                    error_log('Activity logging failed: ' . $e->getMessage());
                }
                
                $this->addFlash('success', 'User deleted successfully.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to delete user: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('admin_users_index');
    }

    /**
     * Helper method to log activities
     */
    private function logActivity(string $action, string $entityType, ?int $entityId, ?string $entityName, string $description): void
    {
        $user = $this->getUser();
        $request = $this->requestStack->getCurrentRequest();
        
        $activityLog = ActivityLog::create(
            $action,
            $entityType,
            $entityId,
            $entityName,
            $user,
            $description
        );

        if ($request) {
            $activityLog->setIpAddress($request->getClientIp())
                       ->setUserAgent($request->headers->get('User-Agent'));
        }

        $this->entityManager->persist($activityLog);
        $this->entityManager->flush();
    }
}
