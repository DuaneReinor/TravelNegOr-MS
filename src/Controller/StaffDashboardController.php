<?php

namespace App\Controller;

use App\Repository\HotelRepository;
use App\Repository\DestinationRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staff', name: 'staff_')]
#[IsGranted('ROLE_STAFF')]
class StaffDashboardController extends AbstractController
{
    /**
     * 🏠 STAFF DASHBOARD PAGE
     * 🔗 Final Path: /staff/
     * 🔖 Final Name: staff_dashboard
     */
    #[Route('/', name: 'dashboard')]
    public function index(
        UserRepository $userRepo,
        HotelRepository $hotelRepository,
        DestinationRepository $destinationRepository
    ): Response {
        $user = $this->getUser();
        
        // Get staff-specific data
        $staffDestinations = $destinationRepository->findBy(['createdBy' => $user]);
        $totalStaffDestinations = count($staffDestinations);
        
        // Get total counts for context
        $totalHotels = count($hotelRepository->findAll());
        $totalDestinations = count($destinationRepository->findAll());
        $totalUsers = count($userRepo->findAll());

        // Get staff's own destinations for display
        $myDestinations = $destinationRepository->findBy(['createdBy' => $user]);
        
        // Get recent hotels (limit to 3 for display)
        $recentHotels = $hotelRepository->findBy([], ['id' => 'DESC'], 3);

        return $this->render('staff/dashboard/index.html.twig', [
            'page_title' => 'Staff Dashboard',
            'totalStaffDestinations' => $totalDestinations,
            'totalHotels' => $totalHotels,
            'totalDestinations' => $totalDestinations,
            'totalUsers' => $totalUsers,
            'myDestinations' => $myDestinations,
            'recentHotels' => $recentHotels,
            'currentUser' => $user,
        ]);
    }
}