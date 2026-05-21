<?php

namespace App\Controller;

use App\Service\TeamMembersProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class AboutController extends AbstractController
{
    #[Route('/about', name: 'app_about')]
    public function index(TeamMembersProvider $teamMembersProvider): Response
    {
        return $this->render('about/index.html.twig', [
            'team' => $teamMembersProvider->all(),
        ]);
    }
}
