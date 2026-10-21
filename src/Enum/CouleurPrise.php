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

  public function pastille(): string
  {
    return match ($this) {
      self::Jaune => 'hold-yellow',
      self::Orange => 'hold-orange',
      self::Vert => 'hold-green',
      self::Bleu => 'hold-blue',
      self::Rouge => 'hold-red',
      self::Rose => 'hold-pink',
      self::Violet => 'hold-violet',
      self::Noir => 'hold-black',
      self::Blanc => 'hold-white',
    };
  }

  public function explication(): string
  {
    return match ($this) {
      self::Jaune => 'Prises jaune vif. Les plus lumineuses du mur, elles se repèrent depuis l’entrée du secteur.',
      self::Orange => 'Prises orange. Plus chaudes que le jaune, elles restent nettes sur un mur clair.',
      self::Vert => 'Prises vertes. Sur le mur, ce vert marque un seul bloc : ce n’est pas la couleur du tapis.',
      self::Bleu => 'Prises bleues. Froides, elles ressortent sur les volumes en bois.',
      self::Rouge => 'Prises rouges. Très contrastées, sur un pan clair comme sur un volume sombre.',
      self::Rose => 'Prises roses. Plus claires que le rouge : ce n’est pas le même bloc.',
      self::Violet => 'Prises violettes. Plus sombres que le rose, elles se lisent surtout de près.',
      self::Noir => 'Prises noires. Elles se confondent avec les volumes foncés : on les cherche aussi à la main.',
      self::Blanc => 'Prises blanches. Proches de la couleur du mur, ce sont les plus discrètes.',
    };
  }
}
