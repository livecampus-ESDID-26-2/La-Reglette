<?php

namespace App\Entity;

use App\Repository\AscensionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AscensionRepository::class)]
#[ORM\Index(columns: ['bloc_id', 'date_reussite'])]
#[ORM\Index(columns: ['grimpeur_id', 'bloc_id'])]
class Ascension
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  private ?int $id = null;

  #[ORM\Column]
  private ?\DateTimeImmutable $dateReussite = null;

  #[ORM\Column]
  #[Assert\Range(min: 1, max: 5)]
  private ?int $note = null;

  #[ORM\Column(type: Types::TEXT, nullable: true)]
  private ?string $commentaire = null;

  #[ORM\ManyToOne(inversedBy: 'ascensions')]
  #[ORM\JoinColumn(nullable: false)]
  private ?User $grimpeur = null;

  #[ORM\ManyToOne(inversedBy: 'ascensions')]
  #[ORM\JoinColumn(nullable: false)]
  private ?Bloc $bloc = null;

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getDateReussite(): ?\DateTimeImmutable
  {
    return $this->dateReussite;
  }

  public function setDateReussite(\DateTimeImmutable $dateReussite): static
  {
    $this->dateReussite = $dateReussite;

    return $this;
  }

  public function getNote(): ?int
  {
    return $this->note;
  }

  public function setNote(int $note): static
  {
    $this->note = $note;

    return $this;
  }

  public function getCommentaire(): ?string
  {
    return $this->commentaire;
  }

  public function setCommentaire(?string $commentaire): static
  {
    $this->commentaire = $commentaire;

    return $this;
  }

  public function getGrimpeur(): ?User
  {
    return $this->grimpeur;
  }

  public function setGrimpeur(?User $grimpeur): static
  {
    $this->grimpeur = $grimpeur;

    return $this;
  }

  public function getBloc(): ?Bloc
  {
    return $this->bloc;
  }

  public function setBloc(?Bloc $bloc): static
  {
    $this->bloc = $bloc;

    return $this;
  }
}
