<?php

namespace App\Message;

final class RecalculerStatistiquesBloc
{
  public function __construct(private readonly int $blocId) {}

  public function getBlocId(): int
  {
    return $this->blocId;
  }
}
