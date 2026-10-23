<?php

namespace App\Controller;

use App\Entity\User;
use App\Progression\CalculNiveau;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ProfileController extends AbstractController
{
  #[Route('/profil', name: 'app_profil')]
  #[IsGranted('ROLE_GRIMPEUR')]
  public function index(CalculNiveau $niveaux): Response
  {
    $grimpeur = $this->getUser();
    if (!($grimpeur instanceof User)) {
      throw $this->createAccessDeniedException();
    }

    $niveau = $niveaux->pour($grimpeur);

    return $this->render('profil/index.html.twig', [
      'niveau' => $niveau,
      'recommandations' => $niveaux->recommander($grimpeur, $niveau),
    ]);
  }
}
