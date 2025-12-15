<?php

namespace App\Controller;

use App\Entity\ActivityLog;
use App\Entity\Hotel;
use App\Form\HotelType;
use App\Repository\HotelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/hotels', name: 'admin_hotels_')]
#[IsGranted('ROLE_ADMIN')]
class AdminHotelController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(HotelRepository $hotelRepository): Response
    {
        $hotels = $hotelRepository->findAll();

        return $this->render('admin/hotels/index.html.twig', [
            'hotels' => $hotels,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $hotel = new Hotel();
        $form = $this->createForm(HotelType::class, $hotel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $imageFile = $form->get('image')->getData();

                if ($imageFile) {
                    $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
                    if (!is_dir($uploadsDir)) {
                        mkdir($uploadsDir, 0755, true);
                    }
                    
                    $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                    $imageFile->move($uploadsDir, $newFilename);
                    $hotel->setImage($newFilename);
                }

                $this->entityManager->persist($hotel);
                $this->entityManager->flush();

                // Log the activity with error handling
                try {
                    $this->logActivity('CREATE', 'Hotel', $hotel->getId(), $hotel->getName(),
                        "Created hotel: {$hotel->getName()} in {$hotel->getLocation()}");
                } catch (\Exception $e) {
                    // Don't fail the entire operation if logging fails
                    error_log('Activity logging failed: ' . $e->getMessage());
                }

                $this->addFlash('success', 'Hotel created successfully!');
                return $this->redirectToRoute('admin_hotels_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to create hotel: ' . $e->getMessage());
            }
        } elseif ($form->isSubmitted()) {
            // Form submitted but not valid - collect errors
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            if (!empty($errors)) {
                $this->addFlash('error', 'Form validation failed: ' . implode(', ', $errors));
            }
        }

        return $this->render('admin/hotels/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Hotel $hotel): Response
    {
        return $this->render('admin/hotels/show.html.twig', [
            'hotel' => $hotel,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Hotel $hotel): Response
    {
        $form = $this->createForm(HotelType::class, $hotel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $imageFile = $form->get('image')->getData();
                if ($imageFile) {
                    $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
                    if (!is_dir($uploadsDir)) {
                        mkdir($uploadsDir, 0755, true);
                    }
                    
                    $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                    $imageFile->move($uploadsDir, $newFilename);
                    $hotel->setImage($newFilename);
                }

                $this->entityManager->flush();

                // Log the activity with error handling
                try {
                    $this->logActivity('UPDATE', 'Hotel', $hotel->getId(), $hotel->getName(),
                        "Updated hotel: {$hotel->getName()}");
                } catch (\Exception $e) {
                    error_log('Activity logging failed: ' . $e->getMessage());
                }

                $this->addFlash('success', 'Hotel updated successfully!');
                return $this->redirectToRoute('admin_hotels_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to update hotel: ' . $e->getMessage());
            }
        } elseif ($form->isSubmitted()) {
            // Form submitted but not valid - collect errors
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            if (!empty($errors)) {
                $this->addFlash('error', 'Form validation failed: ' . implode(', ', $errors));
            }
        }

        return $this->render('admin/hotels/edit.html.twig', [
            'form' => $form->createView(),
            'hotel' => $hotel,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, int $id, HotelRepository $hotelRepository): Response
    {
        $hotel = $hotelRepository->find($id);

        if (!$hotel) {
            $this->addFlash('error', 'The hotel could not be found.');
            return $this->redirectToRoute('admin_hotels_index');
        }

        if ($this->isCsrfTokenValid('delete' . $hotel->getId(), $request->getPayload()->getString('_token'))) {
            try {
                $hotelName = $hotel->getName();
                $hotelId = $hotel->getId();
                
                $this->entityManager->remove($hotel);
                $this->entityManager->flush();
                
                // Log the activity with error handling
                try {
                    $this->logActivity('DELETE', 'Hotel', $hotelId, $hotelName, 
                        "Deleted hotel: {$hotelName}");
                } catch (\Exception $e) {
                    error_log('Activity logging failed: ' . $e->getMessage());
                }
                
                $this->addFlash('success', 'Hotel deleted successfully.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to delete hotel: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('admin_hotels_index');
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
