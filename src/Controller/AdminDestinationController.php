<?php

namespace App\Controller;

use App\Repository\DestinationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/destinations')]
class AdminDestinationController extends AbstractController
{
    #[Route('/', name: 'admin_destinations_index')]
    public function index(DestinationRepository $destinationRepository): Response
    {
        $destinations = $destinationRepository->findAll();

        return $this->render('admin/destinations/index.html.twig', [
            'destinations' => $destinations,
        ]);
    }
}
