<?php
// src/Controller/AdminDestinationController.php
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

#[Route('/admin/destinations', name: 'admin_destinations_')]
#[IsGranted('ROLE_STAFF')] // staff (and admin via hierarchy) can access
class AdminDestinationController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(DestinationRepository $repo): Response
    {
        return $this->render('admin/destinations/index.html.twig', [
            'page_title' => 'Manage Destinations',
            'destinations' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $destination = new Destination();
        $form = $this->createForm(DestinationType::class, $destination);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($destination);
            $em->flush();
            $this->addFlash('success', 'Destination created.');
            return $this->redirectToRoute('admin_destinations_index');
        }

        return $this->render('admin/destinations/new.html.twig', [
            'page_title' => 'Add Destination',
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET','POST'])]
    public function edit(Request $request, Destination $destination, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(DestinationType::class, $destination);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
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
        if ($this->isCsrfTokenValid('delete'.$destination->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($destination);
            $em->flush();
            $this->addFlash('success', 'Destination deleted.');
        }
        return $this->redirectToRoute('admin_destinations_index');
    }
}
