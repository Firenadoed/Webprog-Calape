<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TrialController extends AbstractController
{
    #[Route('/trial', name: 'pokesearch_trial')]
    public function index(): Response
    {
        return $this->render('trial/pokesearch.html.twig', [
            'controller_name' => 'TrialController',
        ]);
    }
}
