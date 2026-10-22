<?php

namespace App\MessageHandler;

use App\Entity\Bloc;
use App\Message\RecalculerStatistiquesBloc;
use App\Repository\AscensionRepository;
use App\Repository\BlocRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RecalculerStatistiquesBlocHandler
{
  public function __construct(
    private readonly BlocRepository $blocs,
    private readonly AscensionRepository $ascensions,
    private readonly EntityManagerInterface $em,
  ) {}

  public function __invoke(RecalculerStatistiquesBloc $message): void
  {
    $bloc = $this->blocs->find($message->getBlocId());
    if (!($bloc instanceof Bloc)) {
      return;
    }

    $stats = $this->ascensions->agregerPourBloc($bloc);
    $bloc->setNombreReussites($stats['nombre']);
    $bloc->setNoteMoyenne($stats['moyenne']);
    $this->em->flush();
  }
}
