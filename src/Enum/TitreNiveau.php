<?php

namespace App\Enum;

enum TitreNiveau: int
{
  case PremiersPas = 1;
  case Regulier = 2;
  case ALaise = 3;
  case Confirme = 4;
  case Solide = 5;
  case Engage = 6;
  case Expert = 7;
  case Reference = 8;

  public function label(): string
  {
    return match ($this) {
      self::PremiersPas => 'Premiers pas',
      self::Regulier => 'Régulier',
      self::ALaise => 'À l\'aise',
      self::Confirme => 'Confirmé',
      self::Solide => 'Solide',
      self::Engage => 'Engagé',
      self::Expert => 'Expert',
      self::Reference => 'Référence',
    };
  }

  public static function libelle(int $niveau): string
  {
    return self::tryFrom($niveau)?->label() ?? 'Niveau ' . $niveau;
  }
}
