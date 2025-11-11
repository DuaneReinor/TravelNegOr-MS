<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        // If the user is already logged in, you can redirect to dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('admin_hotels_index'); // or your main admin page route
        }

        // Otherwise, show the homepage (this Twig file)
        return $this->render('index.html.twig');
    }
}
