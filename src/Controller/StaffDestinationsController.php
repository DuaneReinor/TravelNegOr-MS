<?php

namespace App\Controller;

use App\Entity\Destination;
use App\Form\DestinationType;
use App\Repository\DestinationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staff/destinations', name: 'staff_destinations_')]
#[IsGranted('ROLE_STAFF')]
class StaffDestinationsController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(DestinationRepository $repo): Response
    {
        $user = $this->getUser();
        
        // Staff have the same access as admin - can see all destinations
        $destinations = $repo->findAll();
        
        return $this->render('staff/destinations/index.html.twig', [
            'page_title' => 'Destinations',
            'destinations' => $destinations,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $destination = new Destination();
        $destination->setCreatedBy($this->getUser());
        
        $form = $this->createForm(DestinationType::class, $destination);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($destination);
            $em->flush();
            $this->addFlash('success', 'Destination created successfully!');
            return $this->redirectToRoute('staff_destinations_index');
        }

        return $this->render('staff/destinations/new.html.twig', [
            'destination' => $destination,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Destination $destination): Response
    {
        // Staff have full access - no restrictions

        return $this->render('staff/destinations/show.html.twig', [
            'destination' => $destination,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET','POST'])]
    public function edit(Request $request, Destination $destination, EntityManagerInterface $em): Response
    {
        // Staff have full access - no restrictions like admin

        $form = $this->createForm(DestinationType::class, $destination);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Destination updated successfully!');
            return $this->redirectToRoute('staff_destinations_index');
        }

        return $this->render('staff/destinations/edit.html.twig', [
            'destination' => $destination,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Destination $destination, EntityManagerInterface $em): Response
    {
        // Staff have full access - no restrictions like admin

        if ($this->isCsrfTokenValid('delete'.$destination->getId(), $request->request->get('_token'))) {
            $em->remove($destination);
            $em->flush();
            $this->addFlash('success', 'Destination deleted successfully.');
        }

        return $this->redirectToRoute('staff_destinations_index');
    }
}