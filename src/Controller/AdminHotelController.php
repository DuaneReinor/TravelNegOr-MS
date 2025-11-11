<?php

namespace App\Controller;

use App\Entity\Hotel;
use App\Form\HotelType;
use App\Repository\HotelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/hotels', name: 'admin_hotels_')]
class AdminHotelController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(HotelRepository $hotelRepository): Response
    {
        $hotels = $hotelRepository->findAll();

        return $this->render('admin/hotels/index.html.twig', [
            'hotels' => $hotels,
        ]);
    }

   #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager): Response
{
    $hotel = new Hotel();
    $form = $this->createForm(HotelType::class, $hotel);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $imageFile = $form->get('image')->getData(); // 👈 get uploaded file

        if ($imageFile) {
            // Create a unique filename
            $newFilename = uniqid() . '.' . $imageFile->guessExtension();

            // Move the file to /public/uploads
            $imageFile->move(
                $this->getParameter('kernel.project_dir') . '/public/uploads',
                $newFilename
            );

            // Save the filename in the entity
            $hotel->setImage($newFilename);
        }

        $entityManager->persist($hotel);
        $entityManager->flush();

        $this->addFlash('success', 'Hotel created successfully!');
        return $this->redirectToRoute('admin_hotels_index');
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
public function edit(Request $request, Hotel $hotel, EntityManagerInterface $entityManager): Response
{
    $form = $this->createForm(HotelType::class, $hotel);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $imageFile = $form->get('image')->getData();

        if ($imageFile) {
            $newFilename = uniqid() . '.' . $imageFile->guessExtension();
            $imageFile->move(
                $this->getParameter('kernel.project_dir') . '/public/uploads',
                $newFilename
            );

            $hotel->setImage($newFilename);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Hotel updated successfully!');
        return $this->redirectToRoute('admin_hotels_index');
    }

    return $this->render('admin/hotels/edit.html.twig', [
        'form' => $form->createView(),
        'hotel' => $hotel,
    ]);
}


   #[Route('/{id}', name: 'delete', methods: ['POST'])]
public function delete(Request $request, int $id, HotelRepository $hotelRepository, EntityManagerInterface $entityManager): Response
{
    $hotel = $hotelRepository->find($id);

    if (!$hotel) {
        $this->addFlash('error', 'The hotel could not be found.');
        return $this->redirectToRoute('admin_hotels_index');
    }

    if ($this->isCsrfTokenValid('delete' . $hotel->getId(), $request->getPayload()->getString('_token'))) {
        $entityManager->remove($hotel);
        $entityManager->flush();
        $this->addFlash('success', 'Hotel deleted successfully.');
    }

    return $this->redirectToRoute('admin_hotels_index');
}

}
