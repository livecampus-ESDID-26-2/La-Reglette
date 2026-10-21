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

  public static function tryFromLabel(string $label): ?self
  {
    foreach (self::cases() as $cotation) {
      if ($cotation->label() === $label) {
        return $cotation;
      }
    }

    return null;
  }

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

  public function explication(): string
  {
    return match ($this) {
      self::Trois => 'Très facile. Grosses prises, pieds évidents : on monte presque comme sur une échelle.',
      self::Quatre => 'Facile. Le chemin se lit tout seul, les prises restent confortables.',
      self::Cinq => 'Accessible. Une prise un peu moins grosse, ou un pas à placer.',
      self::CinqPlus => 'Un cran au-dessus du 5. Le mouvement demande déjà un peu d’attention.',
      self::SixA => 'Début du niveau intermédiaire. Il faut une méthode, plus seulement tirer sur les bras.',
      self::SixAPlus => 'Un 6A un peu plus dur : une prise plus petite, ou un pas de plus.',
      self::SixB => 'Le corps doit être placé. Les pieds comptent autant que les mains.',
      self::SixBPlus => 'Un 6B soutenu, souvent un geste précis au milieu du bloc.',
      self::SixC => 'Niveau confirmé. Les prises sont petites, ou le pas est loin.',
      self::SixCPlus => 'Presque un 7A. Ça passe si le geste est juste.',
      self::SeptA => 'Dur. Pour qui grimpe régulièrement. Un mouvement clé bloque souvent.',
      self::SeptAPlus => 'Un 7A avec une prise pire, ou un deuxième passage exigeant.',
      self::SeptB => 'Très dur. La force et la lecture du bloc sont toutes les deux nécessaires.',
      self::SeptBPlus => 'Le haut de ce que la plupart des grimpeurs assidus croisent dans la salle.',
      self::SeptC => 'Niveau expert. Peu de réussites : le geste est spécifique.',
      self::SeptCPlus => 'Tout près du 8A. Une erreur, et on retombe.',
      self::HuitA => 'Le sommet de l’échelle, avant le +. Très peu de grimpeurs le sortent.',
      self::HuitAPlus => 'Le plus dur ouvert ici. Au-dessus, la salle ne cote pas.',
    };
  }
}
