<?php

namespace App\Model;

use App\Entity\Secteur;
use App\Enum\Cotation;

class BlocFiltre
{
  public ?Secteur $secteur = null;

  public ?Cotation $cotationMin = null;

  public ?Cotation $cotationMax = null;
}
