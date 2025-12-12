<?php

namespace App\Controller;

use App\Repository\HotelRepository;
use App\Repository\DestinationRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
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
        // ✅ Fetch all users
        $allUsers = $userRepo->findAll();

        // ✅ Initialize counters
        $adminCount = 0;
        $staffCount = 0;
        $clientCount = 0;

        // ✅ Count users by role
        foreach ($allUsers as $user) {
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                $adminCount++;
            } elseif (in_array('ROLE_STAFF', $user->getRoles())) {
                $staffCount++;
            } else {
                $clientCount++;
            }
        }

        // ✅ You can also show total hotels and destinations if you like
        $totalHotels = count($hotelRepository->findAll());
        $totalDestinations = count($destinationRepository->findAll());

        // ✅ Render dashboard with counts
        return $this->render('admin/dashboard/index.html.twig', [
            'page_title' => 'Dashboard',
            'adminCount' => $adminCount,
            'staffCount' => $staffCount,
            'clientCount' => $clientCount,
            'totalHotels' => $totalHotels,
            'totalDestinations' => $totalDestinations,
        ]);
    }

    
    #[Route('/hotels', name: 'hotels')]
    #[IsGranted('ROLE_ADMIN')]
    public function hotels(HotelRepository $hotelRepository): Response
    {
        $hotels = $hotelRepository->findAll();

        return $this->render('admin/hotels/index.html.twig', [
            'page_title' => 'Manage Hotels',
            'hotels' => $hotels,
        ]);
    }



   
    #[Route('/users', name: 'users')]
    #[IsGranted('ROLE_ADMIN')]
    public function users(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/users/index.html.twig', [
            'page_title' => 'Manage Users',
            'users' => $users,
        ]);
    }
}
