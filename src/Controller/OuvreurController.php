<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\BlocRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_GRIMPEUR')]
final class OuvreurController extends AbstractController
{
  #[Route('/ouvreurs/{id}', name: 'app_ouvreur_show', methods: ['GET'], requirements: ['id' => '\d+'])]
  public function show(User $ouvreur, BlocRepository $blocs): Response
  {
    return $this->render('ouvreur/show.html.twig', [
      'ouvreur' => $ouvreur,
      'blocs' => $blocs->findByOuvreur($ouvreur),
    ]);
  }
}
