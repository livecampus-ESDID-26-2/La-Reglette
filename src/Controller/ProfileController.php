<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ProfileController extends AbstractController
{
  #[Route('/profil', name: 'app_profil')]
  #[IsGranted('ROLE_GRIMPEUR')]
  public function index(): Response
  {
    return $this->render('profil/index.html.twig');
  }
}
