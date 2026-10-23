<?php

namespace App\Progression;

use App\Enum\Cotation;

final readonly class NiveauGrimpeur
{
  public function __construct(
    public int $experience,
    public int $blocsReussis,
    public int $niveau,
    public string $titre,
    public int $experienceDansNiveau,
    public int $experienceDuPalier,
    public int $pourcentage,
    public Cotation $cotationMin,
    public Cotation $cotationMax,
  ) {}
}
