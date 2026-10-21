<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
  public const PASSWORD = 'Reglette2026!';

  public function __construct(private UserPasswordHasherInterface $passwordHasher) {}

  public function load(ObjectManager $manager): void
  {
    foreach ($this->comptes() as [$email, $prenom, $role]) {
      $user = new User();
      $user->setEmail($email);
      $user->setPrenom($prenom);
      $user->setNom('Réglette');
      $user->setRoles([$role]);
      $user->setPassword($this->passwordHasher->hashPassword($user, self::PASSWORD));

      $manager->persist($user);
    }

    $manager->flush();
  }

  /**
   * @return list<array{0: string, 1: string, 2: string}>
   */
  private function comptes(): array
  {
    return [
      ['admin@lareglette.test', 'Admin', 'ROLE_ADMIN'],
      ['ouvreur@lareglette.test', 'Ouvreur', 'ROLE_OUVREUR'],
      ['grimpeur@lareglette.test', 'Grimpeur', 'ROLE_GRIMPEUR'],
    ];
  }
}
