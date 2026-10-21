<?php

namespace App\Controller\Api;

use App\Entity\Bloc;
use App\Entity\Secteur;
use App\Enum\Cotation;
use App\Model\BlocFiltre;
use App\Repository\BlocRepository;
use App\Repository\SecteurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class BlocApiController extends AbstractController
{
  #[Route('/api/blocs', name: 'app_api_blocs', methods: ['GET'])]
  public function liste(Request $request, BlocRepository $blocs, SecteurRepository $secteurs): JsonResponse
  {
    $filtre = new BlocFiltre();
    $secteurId = $request->query->get('secteur');

    if ($secteurId !== null && $secteurId !== '') {
      $secteur = $secteurs->find($secteurId);
      if (!($secteur instanceof Secteur)) {
        return $this->json(['message' => 'Secteur introuvable.'], 404);
      }
      $filtre->secteur = $secteur;
    }

    foreach (['cotationMin', 'cotationMax'] as $parametre) {
      $label = $request->query->get($parametre);
      if ($label === null || $label === '') {
        continue;
      }

      $cotation = Cotation::tryFromLabel((string) $label);
      if (!($cotation instanceof Cotation)) {
        return $this->json(['message' => 'Cotation inconnue.'], 400);
      }

      if ($parametre === 'cotationMin') {
        $filtre->cotationMin = $cotation;
      } else {
        $filtre->cotationMax = $cotation;
      }
    }

    return $this->json(
      array_map(
        fn(Bloc $bloc): array => [
          'id' => $bloc->getId(),
          'nom' => $bloc->getNom(),
          'couleur' => $bloc->getCouleur()?->value,
          'cotation' => $bloc->getCotation()?->label(),
          'secteur' => $bloc->getSecteur()?->getNom(),
          'dateOuverture' => $bloc->getDateOuverture()?->format('Y-m-d'),
          'nombreReussites' => $bloc->getNombreReussites(),
          'noteMoyenne' => $bloc->getNoteMoyenne(),
        ],
        $blocs->findEnPlace($filtre),
      ),
    );
  }
}
