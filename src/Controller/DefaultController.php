<?php

namespace MsShared\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'shared_controller_default')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new shared controller!',
            'path' => 'src/Controller/DefaultController.php',
        ]);
    }
}
