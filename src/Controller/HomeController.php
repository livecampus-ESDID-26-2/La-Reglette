<?php

namespace App\Controller;

use App\Entity\Secteur;
use App\Enum\Cotation;
use App\Enum\CouleurPrise;
use App\Repository\SecteurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
  #[Route('/', name: 'app_home')]
  public function index(SecteurRepository $secteurs): Response
  {
    return $this->render('home/index.html.twig', [
      'photos' => [
        'salle' => [
          'fichier' => 'images/salle-bloc.jpg',
          'alt' =>
            'Salle de bloc : un grand volume bleu couvert de prises jaunes, rouges et grises, avec des grimpeurs au pied du mur.',
          'auteur' => 'Wgreaves',
          'licence' => 'CC BY-SA 4.0',
          'licenceUrl' => 'https://creativecommons.org/licenses/by-sa/4.0',
          'source' => 'https://commons.wikimedia.org/wiki/File:The_Cliffs_at_DUMBO.jpg',
        ],
        'grimpeur' => [
          'fichier' => 'images/grimpeur-bloc.jpg',
          'alt' => 'Grimpeur engagé sur un dévers, entre de gros volumes bleus, jaunes et verts.',
          'auteur' => 'John Kane',
          'licence' => 'CC0',
          'licenceUrl' => 'https://creativecommons.org/publicdomain/zero/1.0/',
          'source' => 'https://commons.wikimedia.org/wiki/File:Man_climbing_in_bouldering_gym.jpg',
        ],
        'tapis' => [
          'fichier' => 'images/mur-et-tapis.jpg',
          'alt' => 'Mur de bloc compact, posé au milieu de grands tapis de réception.',
          'auteur' => 'amrufm',
          'licence' => 'CC BY 2.0',
          'licenceUrl' => 'https://creativecommons.org/licenses/by/2.0',
          'source' => 'https://commons.wikimedia.org/wiki/File:Indoor_Climbing_Wall_Bouldering_Mats.jpg',
        ],
        'prises' => [
          'fichier' => 'images/mur-prises.jpg',
          'alt' => 'Pan de mur sculpté, couvert de prises de toutes les couleurs, avec un tapis noir au sol.',
          'auteur' => 'Onychestra',
          'licence' => 'CC0',
          'licenceUrl' => 'https://creativecommons.org/publicdomain/zero/1.0/',
          'source' => 'https://commons.wikimedia.org/wiki/File:UGA_Bouldering_Wall.jpg',
        ],
      ],
      'lexique' => [
        [
          'terme' => 'Bloc',
          'texte' =>
            'Un itinéraire court, de 3 à 4,5 mètres. Il se lit d’une couleur : un départ, des prises, une arrivée.',
        ],
        [
          'terme' => 'Prise',
          'texte' =>
            'La pièce vissée au mur, pour les mains et les pieds. Une même couleur relie celles d’un même bloc.',
        ],
        [
          'terme' => 'Secteur',
          'texte' =>
            'Une zone de la salle : un pan, un dévers, ou simplement A, B et C. Chaque bloc appartient à un secteur.',
        ],
        [
          'terme' => 'Cotation',
          'texte' =>
            'La difficulté, sur l’échelle française. De 3, très facile, jusqu’à 8A. À partir de 6A, chaque cran existe aussi en « + ».',
        ],
        [
          'terme' => 'Ouverture',
          'texte' =>
            'Créer un bloc en posant ses prises. Le démontage les retire pour libérer le mur, sans effacer l’historique.',
        ],
        [
          'terme' => 'Réussite',
          'texte' =>
            'Grimper le bloc du départ à l’arrivée, sans tomber. On peut le réussir plusieurs fois, et lui laisser une note.',
        ],
      ],
      'roles' => [
        [
          'nom' => 'Grimpeur',
          'texte' =>
            'Le client de la salle, abonné ou de passage. Il consulte les blocs en place et enregistre ses réussites.',
        ],
        [
          'nom' => 'Ouvreur',
          'texte' =>
            'Le salarié qui conçoit et pose les blocs. Il ne modifie que ceux qu’il a ouverts. C’est aussi un grimpeur.',
        ],
        [
          'nom' => 'Admin',
          'texte' => 'Il tient les secteurs, les comptes et les rôles. Il peut modifier toute la salle.',
        ],
      ],
      'couleurs' => CouleurPrise::cases(),
      'cotations' => Cotation::cases(),
      'secteurs' => $this->secteursExpliques($secteurs),
    ]);
  }

  /**
   * @return list<array{nom: string, texte: string}>
   */
  private function secteursExpliques(SecteurRepository $secteurs): array
  {
    $ordre = ['Dalle' => 0, 'Pan' => 1, 'Dévers' => 2, 'Toit' => 3];
    $liste = $secteurs->findBy([], ['nom' => 'ASC']);
    usort(
      $liste,
      static fn(Secteur $a, Secteur $b): int => ($ordre[$a->getNom()] ?? 100) <=> ($ordre[$b->getNom()] ?? 100) ?:
      strcmp((string) $a->getNom(), (string) $b->getNom()),
    );

    return array_map(
      static fn(Secteur $secteur): array => [
        'nom' => (string) $secteur->getNom(),
        'texte' => $secteur->explication(),
      ],
      $liste,
    );
  }
}
