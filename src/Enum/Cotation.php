<?php

namespace App\Enum;

enum Cotation: int
{
  case Trois = 1;
  case Quatre = 2;
  case Cinq = 3;
  case CinqPlus = 4;
  case SixA = 5;
  case SixAPlus = 6;
  case SixB = 7;
  case SixBPlus = 8;
  case SixC = 9;
  case SixCPlus = 10;
  case SeptA = 11;
  case SeptAPlus = 12;
  case SeptB = 13;
  case SeptBPlus = 14;
  case SeptC = 15;
  case SeptCPlus = 16;
  case HuitA = 17;
  case HuitAPlus = 18;

  public function label(): string
  {
    return match ($this) {
      self::Trois => '3',
      self::Quatre => '4',
      self::Cinq => '5',
      self::CinqPlus => '5+',
      self::SixA => '6A',
      self::SixAPlus => '6A+',
      self::SixB => '6B',
      self::SixBPlus => '6B+',
      self::SixC => '6C',
      self::SixCPlus => '6C+',
      self::SeptA => '7A',
      self::SeptAPlus => '7A+',
      self::SeptB => '7B',
      self::SeptBPlus => '7B+',
      self::SeptC => '7C',
      self::SeptCPlus => '7C+',
      self::HuitA => '8A',
      self::HuitAPlus => '8A+',
    };
  }
}
