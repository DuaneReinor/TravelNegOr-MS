<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DebugController extends AbstractController
{
    #[Route('/debug/user', name: 'debug_user')]
    public function debugUser(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new Response('No user logged in');
        }
        
        $roles = $user->getRoles();
        
        $content = "User Email: " . $user->getUserIdentifier() . "\n";
        $content .= "User Roles: " . implode(', ', $roles) . "\n";
        $content .= "Is Staff: " . ($this->isGranted('ROLE_STAFF') ? 'Yes' : 'No') . "\n";
        $content .= "Is Admin: " . ($this->isGranted('ROLE_ADMIN') ? 'Yes' : 'No') . "\n";
        
        return new Response('<pre>' . $content . '</pre>');
    }
}