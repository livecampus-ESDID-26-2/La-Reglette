<?php

namespace App\MessageHandler;

use App\Entity\Bloc;
use App\Message\ListerBlocsADemonter;
use App\Repository\BlocRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ListerBlocsADemonterHandler
{
  public function __construct(private readonly BlocRepository $blocs, private readonly LoggerInterface $logger) {}

  public function __invoke(ListerBlocsADemonter $message): void
  {
    $depuis = (new \DateTimeImmutable('today'))->modify('-14 days');
    $trouves = $this->blocs->findEnPlaceSansReussiteRecente($depuis);

    $this->logger->info('Blocs en place sans réussite depuis le {depuis}.', [
      'depuis' => $depuis->format('Y-m-d'),
      'nombre' => count($trouves),
      'blocs' => array_map(
        static fn(Bloc $bloc): string => sprintf(
          '%s — %s — %s — ouvert le %s',
          $bloc->getNom(),
          $bloc->getSecteur()?->getNom(),
          $bloc->getCotation()?->label(),
          $bloc->getDateOuverture()?->format('d/m/Y'),
        ),
        $trouves,
      ),
    ]);
  }
}
