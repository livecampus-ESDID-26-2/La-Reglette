<?php

namespace App\Command;

use App\Entity\Bloc;
use App\Repository\BlocRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:blocs:demontage', description: 'Démonte les blocs en place ouverts depuis plus de N semaines')]
final class DemontageBlocsCommand extends Command
{
  public function __construct(private readonly BlocRepository $blocs, private readonly EntityManagerInterface $em)
  {
    parent::__construct();
  }

  protected function configure(): void
  {
    $this->addOption('semaines', null, InputOption::VALUE_REQUIRED, 'Âge minimum, en semaines', '6')->addOption(
      'dry-run',
      null,
      InputOption::VALUE_NONE,
      'Affiche les blocs sans les démonter',
    );
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $io = new SymfonyStyle($input, $output);
    $semaines = $input->getOption('semaines');
    if (!is_string($semaines) || preg_match('/^[1-9]\d*$/', $semaines) !== 1) {
      $io->error('Le nombre de semaines doit être un entier supérieur à 0.');

      return Command::FAILURE;
    }

    $avant = (new \DateTimeImmutable('today'))->modify('-' . $semaines . ' weeks');
    $aDemonter = $this->blocs->findEnPlaceOuvertsAvant($avant);
    if ($aDemonter === []) {
      $io->info(sprintf('Aucun bloc en place n\'a plus de %d semaines.', (int) $semaines));

      return Command::SUCCESS;
    }

    $io->table(
      ['Nom', 'Secteur', 'Cotation', 'Ouverture'],
      array_map(
        static fn(Bloc $bloc): array => [
          (string) $bloc->getNom(),
          (string) $bloc->getSecteur()?->getNom(),
          (string) $bloc->getCotation()?->label(),
          $bloc->getDateOuverture()?->format('d/m/Y') ?? '',
        ],
        $aDemonter,
      ),
    );

    if ($input->getOption('dry-run') === true) {
      $io->note('Simulation : aucun bloc n\'a été démonté.');

      return Command::SUCCESS;
    }

    if (!$io->confirm(sprintf('Démonter ces %d blocs ?', count($aDemonter)), false)) {
      $io->warning('Démontage annulé.');

      return Command::SUCCESS;
    }

    $maintenant = new \DateTimeImmutable();
    foreach ($aDemonter as $bloc) {
      $bloc->setDateDemontage($maintenant);
    }
    $this->em->flush();
    $io->success(sprintf('%d blocs démontés.', count($aDemonter)));

    return Command::SUCCESS;
  }
}
