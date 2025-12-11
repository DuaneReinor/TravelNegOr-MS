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

#[IsGranted('ROLE_STAFF')]  // Staff + Admin can access
#[Route('/admin/destinations', name: 'admin_destinations_')]
class DestinationController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(DestinationRepository $destinationRepository): Response
    {
        $user = $this->getUser();

        // Admin can see all destinations
        if ($this->isGranted('ROLE_ADMIN')) {
            $destinations = $destinationRepository->findAll();
        } else {
            // Staff can see only destinations they created
            $destinations = $destinationRepository->findBy(['createdBy' => $user]);
        }

        return $this->render('admin/destinations/index.html.twig', [
            'destinations' => $destinations,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $destination = new Destination();
        $destination->setCreatedBy($this->getUser());

        $form = $this->createForm(DestinationType::class, $destination);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($destination);
            $entityManager->flush();

            return $this->redirectToRoute('admin_destinations_index');
        }

        return $this->render('admin/destinations/new.html.twig', [
            'destination' => $destination,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Destination $destination): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/destinations/show.html.twig', [
            'destination' => $destination,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Destination $destination, EntityManagerInterface $entityManager): Response
    {
        // Staff cannot edit records they did not create
        if (!$this->isGranted('ROLE_ADMIN') && $destination->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot edit this destination.');
        }

        $form = $this->createForm(DestinationType::class, $destination);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_destinations_index');
        }

        return $this->render('admin/destinations/edit.html.twig', [
            'destination' => $destination,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Destination $destination, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $destination->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot delete this destination.');
        }

        if ($this->isCsrfTokenValid('delete' . $destination->getId(), $request->request->get('_token'))) {
            $entityManager->remove($destination);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_destinations_index');
    }
}
