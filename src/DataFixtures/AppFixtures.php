<?php

namespace App\DataFixtures;

use App\Entity\Ascension;
use App\Entity\Bloc;
use App\Entity\Secteur;
use App\Entity\User;
use App\Enum\Cotation;
use App\Enum\CouleurPrise;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Random\Engine\Mt19937;
use Random\Randomizer;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
  public const PASSWORD = 'Reglette2026!';

  public function __construct(private UserPasswordHasherInterface $passwordHasher) {}

  public function load(ObjectManager $manager): void
  {
    $random = new Randomizer(new Mt19937(20261021));
    $aujourdhui = new \DateTimeImmutable('today');

    $ouvreurs = [
      $this->compte($manager, 'ouvreur1@lareglette.test', 'Léa', 'Faure', 'ROLE_OUVREUR'),
      $this->compte($manager, 'ouvreur2@lareglette.test', 'Marc', 'Olivier', 'ROLE_OUVREUR'),
      $this->compte($manager, 'ouvreur3@lareglette.test', 'Nina', 'Costa', 'ROLE_OUVREUR'),
    ];
    $this->compte($manager, 'admin@lareglette.test', 'Admin', 'Réglette', 'ROLE_ADMIN');

    $grimpeurs = [];
    foreach ($this->grimpeurs() as $index => [$prenom, $nom]) {
      $grimpeurs[] = $this->compte(
        $manager,
        sprintf('grimpeur%02d@lareglette.test', $index + 1),
        $prenom,
        $nom,
        'ROLE_GRIMPEUR',
      );
    }

    $secteurs = [];
    foreach (['Dévers', 'Dalle', 'Toit', 'Pan'] as $nom) {
      $secteur = new Secteur();
      $secteur->setNom($nom);
      $manager->persist($secteur);
      $secteurs[] = $secteur;
    }

    $couleurs = CouleurPrise::cases();
    $cotations = Cotation::cases();
    $noms = $this->nomsDeBlocs();
    $blocs = [];

    foreach ($noms as $index => $nom) {
      $jours = 59 - (int) round(($index * 59) / 39);
      $ouverture = $aujourdhui->modify('-' . $jours . ' days')->setTime(12, 0);
      $bloc = new Bloc();
      $bloc->setNom($nom);
      $bloc->setCouleur($couleurs[$index % count($couleurs)]);
      $bloc->setCotation($index < count($cotations) ? $cotations[$index] : $cotations[$random->getInt(0, 7)]);
      $bloc->setDateOuverture($ouverture);
      $bloc->setSecteur($secteurs[$index % count($secteurs)]);
      $bloc->setOuvreur($ouvreurs[$index % count($ouvreurs)]);
      $manager->persist($bloc);
      $blocs[] = $bloc;
    }

    foreach ([2, 5, 8, 11, 14, 18, 21, 24, 28, 32] as $index) {
      $ouverture = $blocs[$index]->getDateOuverture();
      $blocs[$index]->setDateDemontage($this->jourEntre($ouverture->modify('+2 days'), $aujourdhui, $random));
    }

    foreach ($blocs as $index => $bloc) {
      $nombre = $this->nombreDeReussites($bloc, $index);
      for ($reussite = 0; $reussite < $nombre; $reussite++) {
        $grimpeur = $grimpeurs[$random->getInt(0, count($grimpeurs) - 1)];
        if ($index === 39 && $grimpeur === $grimpeurs[0]) {
          $grimpeur = $grimpeurs[1];
        }

        $fin = $index === 0 ? $bloc->getDateOuverture()->modify('+5 days') : $bloc->getDateDemontage() ?? $aujourdhui;
        $this->ascension(
          $manager,
          $bloc,
          $grimpeur,
          $this->jourEntre($bloc->getDateOuverture(), $fin, $random),
          $random,
        );
      }
    }

    foreach ($blocs as $bloc) {
      $this->recalculer($bloc);
    }

    $manager->flush();
  }

  private function compte(ObjectManager $manager, string $email, string $prenom, string $nom, string $role): User
  {
    $user = new User();
    $user->setEmail($email);
    $user->setPrenom($prenom);
    $user->setNom($nom);
    $user->setRoles([$role]);
    $user->setPassword($this->passwordHasher->hashPassword($user, self::PASSWORD));
    $manager->persist($user);

    return $user;
  }

  private function ascension(
    ObjectManager $manager,
    Bloc $bloc,
    User $grimpeur,
    \DateTimeImmutable $date,
    Randomizer $random,
  ): void {
    $ascension = new Ascension();
    $ascension->setGrimpeur($grimpeur);
    $ascension->setDateReussite($date);
    $rang = $bloc->getCotation()->value;
    $ascension->setNote($random->getInt(max(1, 4 - intdiv($rang, 4)), 5));
    if ($random->getInt(1, 3) === 1) {
      $commentaires = ['Bien tenu.', 'Les pieds sont bons.', 'Ça passe en statique.', 'Un peu aléatoire au départ.'];
      $ascension->setCommentaire($commentaires[$random->getInt(0, count($commentaires) - 1)]);
    }
    $bloc->addAscension($ascension);
    $manager->persist($ascension);
  }

  private function nombreDeReussites(Bloc $bloc, int $index): int
  {
    if ($index === 0) {
      return 3;
    }

    if ($bloc->getCotation() === Cotation::HuitAPlus) {
      return 0;
    }

    return max(1, (int) round((19 - $bloc->getCotation()->value) * 0.6));
  }

  private function recalculer(Bloc $bloc): void
  {
    $nombre = 0;
    $somme = 0;
    foreach ($bloc->getAscensions() as $ascension) {
      $nombre++;
      $somme += $ascension->getNote();
    }

    $bloc->setNombreReussites($nombre);
    $bloc->setNoteMoyenne($nombre === 0 ? null : round($somme / $nombre, 2));
  }

  private function jourEntre(\DateTimeImmutable $debut, \DateTimeImmutable $fin, Randomizer $random): \DateTimeImmutable
  {
    $debut = $debut->setTime(0, 0);
    $fin = $fin->setTime(0, 0);
    if ($fin < $debut) {
      $fin = $debut;
    }

    $decalage = $random->getInt(0, (int) $debut->diff($fin)->days);

    return $debut->modify('+' . $decalage . ' days')->setTime(12, 0);
  }

  /**
   * @return list<array{0: string, 1: string}>
   */
  private function grimpeurs(): array
  {
    return [
      ['Camille', 'Martin'],
      ['Hugo', 'Bernard'],
      ['Chloé', 'Petit'],
      ['Louis', 'Robert'],
      ['Emma', 'Richard'],
      ['Jules', 'Durand'],
      ['Léa', 'Moreau'],
      ['Adam', 'Laurent'],
      ['Manon', 'Simon'],
      ['Nathan', 'Michel'],
      ['Inès', 'Garcia'],
      ['Ethan', 'David'],
      ['Jade', 'Bertrand'],
      ['Raphaël', 'Roux'],
      ['Louise', 'Vincent'],
      ['Arthur', 'Fournier'],
      ['Zoé', 'Girard'],
      ['Tom', 'Bonnet'],
      ['Sarah', 'Mercier'],
      ['Noah', 'Dupont'],
    ];
  }

  /**
   * @return list<string>
   */
  private function nomsDeBlocs(): array
  {
    return [
      'Arête jaune',
      'Dièdre bleu',
      'Réglette noire',
      'Bac rose',
      'Volume vert',
      'Proue orange',
      'Dalle blanche',
      'Toit violet',
      'Départ assis',
      'Traversée',
      'Compression',
      'Lolotte',
      'Dyno',
      'Coincement',
      'Plat',
      'Boule jaune',
      'Pinces bleues',
      'Bi-doigts',
      'Épaule',
      'Talon',
      'Contre-pointe',
      'Crochet',
      'Inversée',
      'Gaston',
      'Dévers court',
      'Toit long',
      'Rampe',
      'Diagonale',
      'Bloc bas',
      'Haut de dalle',
      'Angle',
      'Pilier',
      'Arche',
      'Fissure',
      'Proue raide',
      'Mur vertical',
      'Sortie loin',
      'Marche',
      'Pas de bloc',
      'Réglette fine',
    ];
  }
}
