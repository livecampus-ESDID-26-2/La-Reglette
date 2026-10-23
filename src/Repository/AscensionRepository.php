<?php

namespace App\Repository;

use App\Entity\Ascension;
use App\Entity\Bloc;
use App\Entity\User;
use App\Enum\Cotation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ascension>
 */
class AscensionRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Ascension::class);
  }

  /**
   * @return list<Ascension>
   */
  public function findParGrimpeur(User $grimpeur): array
  {
    return $this->createQueryBuilder('a')
      ->addSelect('b', 's')
      ->innerJoin('a.bloc', 'b')
      ->innerJoin('b.secteur', 's')
      ->andWhere('a.grimpeur = :grimpeur')
      ->setParameter('grimpeur', $grimpeur)
      ->orderBy('a.dateReussite', 'DESC')
      ->getQuery()
      ->getResult();
  }

  /**
   * @return list<Ascension>
   */
  public function findParGrimpeurEtBloc(User $grimpeur, Bloc $bloc): array
  {
    return $this->createQueryBuilder('a')
      ->andWhere('a.grimpeur = :grimpeur')
      ->andWhere('a.bloc = :bloc')
      ->setParameter('grimpeur', $grimpeur)
      ->setParameter('bloc', $bloc)
      ->orderBy('a.dateReussite', 'DESC')
      ->getQuery()
      ->getResult();
  }

  /**
   * @return list<array{cotation: Cotation|int|string, nombre: int|string}>
   */
  public function compterBlocsReussisParCotation(User $grimpeur): array
  {
    return $this->createQueryBuilder('a')
      ->select('b.cotation AS cotation', 'COUNT(DISTINCT b.id) AS nombre')
      ->innerJoin('a.bloc', 'b')
      ->andWhere('a.grimpeur = :grimpeur')
      ->setParameter('grimpeur', $grimpeur)
      ->groupBy('b.cotation')
      ->getQuery()
      ->getArrayResult();
  }

  /**
   * @return array{nombre: int, moyenne: ?float}
   */
  public function agregerPourBloc(Bloc $bloc): array
  {
    $ligne = $this->createQueryBuilder('a')
      ->select('COUNT(a.id) AS nombre', 'AVG(a.note) AS moyenne')
      ->andWhere('a.bloc = :bloc')
      ->setParameter('bloc', $bloc)
      ->getQuery()
      ->getSingleResult();

    $nombre = (int) $ligne['nombre'];

    return [
      'nombre' => $nombre,
      'moyenne' => $nombre === 0 || $ligne['moyenne'] === null ? null : round((float) $ligne['moyenne'], 2),
    ];
  }
}
