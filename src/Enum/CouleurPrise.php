<?php

namespace App\Enum;

enum CouleurPrise: string
{
  case Jaune = 'jaune';
  case Orange = 'orange';
  case Vert = 'vert';
  case Bleu = 'bleu';
  case Rouge = 'rouge';
  case Rose = 'rose';
  case Violet = 'violet';
  case Noir = 'noir';
  case Blanc = 'blanc';

  public function label(): string
  {
    return $this->value;
  }
}
