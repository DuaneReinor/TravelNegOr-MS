<?php

namespace App\Controller;

use App\Repository\HotelRepository;
use App\Repository\DestinationRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin', name: 'admin_')]
class AdminDashboardController extends AbstractController
{
    /**
     * 🏠 ADMIN DASHBOARD PAGE
     * 🔗 Final Path: /admin/
     * 🔖 Final Name: admin_dashboard
     */
    #[Route('/', name: 'dashboard')]
    public function index(
        UserRepository $userRepo,
        HotelRepository $hotelRepository,
        DestinationRepository $destinationRepository
    ): Response {
        // ✅ Restrict access to admins only
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Access denied. Admins only.');
        }

        // ✅ Fetch all users
        $allUsers = $userRepo->findAll();

        // ✅ Initialize counters
        $adminCount = 0;
        $clientCount = 0;

        // ✅ Count users by role
        foreach ($allUsers as $user) {
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                $adminCount++;
            } else {
                $clientCount++;
            }
        }

        // ✅ You can also show total hotels and destinations if you like
        $totalHotels = count($hotelRepository->findAll());
        $totalDestinations = count($destinationRepository->findAll());

        // ✅ Render dashboard with counts
        return $this->render('admin/dashboard/index.html.twig', [
            'page_title' => 'Admin Dashboard',
            'adminCount' => $adminCount,
            'clientCount' => $clientCount,
            'totalHotels' => $totalHotels,
            'totalDestinations' => $totalDestinations,
        ]);
    }

    
    #[Route('/hotels', name: 'hotels')]
    public function hotels(HotelRepository $hotelRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Access denied. Admins only.');
        }

        $hotels = $hotelRepository->findAll();

        return $this->render('admin/hotels/index.html.twig', [
            'page_title' => 'Manage Hotels',
            'hotels' => $hotels,
        ]);
    }

    
    #[Route('/destinations', name: 'destinations')]
    public function destinations(DestinationRepository $destinationRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Access denied. Admins only.');
        }

        $destinations = $destinationRepository->findAll();

        return $this->render('admin/destinations/index.html.twig', [
            'page_title' => 'Manage Destinations',
            'destinations' => $destinations,
        ]);
    }

   
    #[Route('/users', name: 'users')]
    public function users(UserRepository $userRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Access denied. Admins only.');
        }

        $users = $userRepository->findAll();

        return $this->render('admin/users/index.html.twig', [
            'page_title' => 'Manage Users',
            'users' => $users,
        ]);
    }
}
