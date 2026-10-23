<?php

namespace App\Progression;

use App\Entity\Bloc;
use App\Entity\User;
use App\Enum\Cotation;
use App\Enum\TitreNiveau;
use App\Repository\AscensionRepository;
use App\Repository\BlocRepository;

final class CalculNiveau
{
  public function __construct(
    private readonly AscensionRepository $ascensions,
    private readonly BlocRepository $blocs,
  ) {}

  public function pour(User $grimpeur): NiveauGrimpeur
  {
    $experience = 0;
    $blocsReussis = 0;
    $plusHaute = null;

    foreach ($this->ascensions->compterBlocsReussisParCotation($grimpeur) as $ligne) {
      $cotation = $this->cotation($ligne['cotation']);
      $nombre = (int) $ligne['nombre'];
      if (!($cotation instanceof Cotation) || $nombre < 1) {
        continue;
      }

      $experience += $nombre * $cotation->experience();
      $blocsReussis += $nombre;
      if ($plusHaute === null || $cotation->value > $plusHaute->value) {
        $plusHaute = $cotation;
      }
    }

    $niveau = 1;
    while ($this->seuil($niveau + 1) <= $experience) {
      $niveau++;
    }

    $debut = $this->seuil($niveau);
    $palier = $this->seuil($niveau + 1) - $debut;
    $dansNiveau = $experience - $debut;
    [$min, $max] = $this->fenetre($plusHaute);

    return new NiveauGrimpeur(
      $experience,
      $blocsReussis,
      $niveau,
      TitreNiveau::libelle($niveau),
      $dansNiveau,
      $palier,
      $palier === 0 ? 100 : (int) floor(($dansNiveau / $palier) * 100),
      $min,
      $max,
    );
  }

  /**
   * @return list<Bloc>
   */
  public function recommander(User $grimpeur, NiveauGrimpeur $niveau): array
  {
    $min = $niveau->cotationMin;
    $max = $niveau->cotationMax;

    for ($essai = 0; $essai < 3; $essai++) {
      $propositions = $this->blocs->findEnPlaceARecommander($grimpeur, $min, $max);
      if (count($propositions) >= 3 || $max === Cotation::HuitAPlus) {
        return $propositions;
      }

      $suivant = Cotation::tryFrom($max->value + 1);
      if (!($suivant instanceof Cotation)) {
        return $propositions;
      }

      $max = $suivant;
    }

    return $propositions;
  }

  private function seuil(int $niveau): int
  {
    $total = 0;
    for ($cran = 1; $cran < $niveau; $cran++) {
      $total += 40 * $cran;
    }

    return $total;
  }

  /**
   * @return array{0: Cotation, 1: Cotation}
   */
  private function fenetre(?Cotation $plusHaute): array
  {
    if ($plusHaute === null) {
      return [Cotation::Trois, Cotation::Cinq];
    }

    $plancher = Cotation::tryFrom(max(Cotation::Trois->value, $plusHaute->value - 1)) ?? $plusHaute;
    $plafond = Cotation::tryFrom(min(Cotation::HuitAPlus->value, $plusHaute->value + 1)) ?? $plusHaute;

    return [$plancher, $plafond];
  }

  private function cotation(mixed $valeur): ?Cotation
  {
    if ($valeur instanceof Cotation) {
      return $valeur;
    }

    if (is_int($valeur) || (is_string($valeur) && ctype_digit($valeur))) {
      return Cotation::tryFrom((int) $valeur);
    }

    return null;
  }
}
