<?php

namespace App\Repository;

use App\Entity\Bloc;
use App\Entity\User;
use App\Enum\Cotation;
use App\Model\BlocFiltre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bloc>
 */
class BlocRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Bloc::class);
  }

  /**
   * @return list<Bloc>
   */
  public function findEnPlace(BlocFiltre $filtre): array
  {
    $blocs = $this->createQueryBuilder('b')
      ->addSelect('s', 'o')
      ->innerJoin('b.secteur', 's')
      ->innerJoin('b.ouvreur', 'o')
      ->andWhere('b.dateDemontage IS NULL')
      ->orderBy('b.dateOuverture', 'DESC')
      ->addOrderBy('b.nom', 'ASC');

    if ($filtre->secteur !== null) {
      $blocs->andWhere('b.secteur = :secteur')->setParameter('secteur', $filtre->secteur);
    }

    if ($filtre->cotationMin !== null) {
      $blocs->andWhere('b.cotation >= :cotationMin')->setParameter('cotationMin', $filtre->cotationMin);
    }

    if ($filtre->cotationMax !== null) {
      $blocs->andWhere('b.cotation <= :cotationMax')->setParameter('cotationMax', $filtre->cotationMax);
    }

    return $blocs->getQuery()->getResult();
  }

  /**
   * @return list<Bloc>
   */
  public function findEnPlaceNonReussisPar(User $grimpeur): array
  {
    return $this->createQueryBuilder('b')
      ->addSelect('s', 'o')
      ->innerJoin('b.secteur', 's')
      ->innerJoin('b.ouvreur', 'o')
      ->andWhere('b.dateDemontage IS NULL')
      ->andWhere('NOT EXISTS (SELECT a.id FROM App\Entity\Ascension a WHERE a.bloc = b AND a.grimpeur = :grimpeur)')
      ->setParameter('grimpeur', $grimpeur)
      ->orderBy('b.dateOuverture', 'DESC')
      ->addOrderBy('b.nom', 'ASC')
      ->getQuery()
      ->getResult();
  }

  /**
   * @return list<Bloc>
   */
  public function findEnPlaceARecommander(User $grimpeur, Cotation $min, Cotation $max, int $limite = 5): array
  {
    return $this->createQueryBuilder('b')
      ->addSelect('s', 'o')
      ->innerJoin('b.secteur', 's')
      ->innerJoin('b.ouvreur', 'o')
      ->andWhere('b.dateDemontage IS NULL')
      ->andWhere('b.cotation >= :min')
      ->andWhere('b.cotation <= :max')
      ->andWhere('NOT EXISTS (SELECT a.id FROM App\Entity\Ascension a WHERE a.bloc = b AND a.grimpeur = :grimpeur)')
      ->setParameter('grimpeur', $grimpeur)
      ->setParameter('min', $min)
      ->setParameter('max', $max)
      ->orderBy('b.cotation', 'ASC')
      ->addOrderBy('b.dateOuverture', 'DESC')
      ->setMaxResults($limite)
      ->getQuery()
      ->getResult();
  }

  /**
   * @return list<Bloc>
   */
  public function findEnPlaceSansReussiteRecente(\DateTimeImmutable $depuis): array
  {
    return $this->createQueryBuilder('b')
      ->addSelect('s')
      ->innerJoin('b.secteur', 's')
      ->andWhere('b.dateDemontage IS NULL')
      ->andWhere('b.dateOuverture <= :depuis')
      ->andWhere('NOT EXISTS (SELECT a.id FROM App\Entity\Ascension a WHERE a.bloc = b AND a.dateReussite >= :depuis)')
      ->setParameter('depuis', $depuis)
      ->orderBy('b.dateOuverture', 'ASC')
      ->addOrderBy('b.nom', 'ASC')
      ->getQuery()
      ->getResult();
  }

  /**
   * @return list<Bloc>
   */
  public function findEnPlaceOuvertsAvant(\DateTimeImmutable $avant): array
  {
    return $this->createQueryBuilder('b')
      ->addSelect('s')
      ->innerJoin('b.secteur', 's')
      ->andWhere('b.dateDemontage IS NULL')
      ->andWhere('b.dateOuverture < :avant')
      ->setParameter('avant', $avant)
      ->orderBy('b.dateOuverture', 'ASC')
      ->addOrderBy('b.nom', 'ASC')
      ->getQuery()
      ->getResult();
  }

  /**
   * @return list<Bloc>
   */
  public function findByOuvreur(User $ouvreur): array
  {
    return $this->createQueryBuilder('b')
      ->addSelect('s')
      ->innerJoin('b.secteur', 's')
      ->andWhere('b.ouvreur = :ouvreur')
      ->setParameter('ouvreur', $ouvreur)
      ->orderBy('b.dateOuverture', 'DESC')
      ->addOrderBy('b.nom', 'ASC')
      ->getQuery()
      ->getResult();
  }
}
