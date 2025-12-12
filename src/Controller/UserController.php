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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/users')]
class UserController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    #[Route('/', name: 'admin_users_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/users/index.html.twig', [
            'page_title' => 'Manage Users',
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_users_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
            $hashed = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashed);

            // Assign default role (e.g., STAFF)
            $user->setRoles(['ROLE_STAFF']);

            $entityManager->persist($user);
            $entityManager->flush();

            // Log the activity
            $name = $user->getFirstName() . ' ' . $user->getLastName();
            $this->logActivity('CREATE', 'User', $user->getId(), $name, 
                "Created user: {$name} ({$user->getEmail()})");

            $this->addFlash('success', 'User created successfully.');
            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/users/new.html.twig', [
            'page_title' => 'Add User',
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_users_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/users/show.html.twig', [
            'page_title' => 'View User',
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_users_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash password if changed
            if ($form->get('password')->getData()) {
                $hashed = $passwordHasher->hashPassword($user, $user->getPassword());
                $user->setPassword($hashed);
            }

            $entityManager->flush();

            // Log the activity
            $name = $user->getFirstName() . ' ' . $user->getLastName();
            $this->logActivity('UPDATE', 'User', $user->getId(), $name, 
                "Updated user: {$name}");

            $this->addFlash('success', 'User updated successfully.');

            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/users/edit.html.twig', [
            'page_title' => 'Edit User',
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_users_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $name = $user->getFirstName() . ' ' . $user->getLastName();
            $userId = $user->getId();
            
            $entityManager->remove($user);
            $entityManager->flush();
            
            // Log the activity
            $this->logActivity('DELETE', 'User', $userId, $name, 
                "Deleted user: {$name} ({$user->getEmail()})");
            
            $this->addFlash('success', 'User deleted successfully.');
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
