<?php

namespace App\Entity;

use App\Repository\SecteurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SecteurRepository::class)]
class Secteur
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  private ?int $id = null;

  #[ORM\Column(length: 255, unique: true)]
  private ?string $nom = null;

  /**
   * @var Collection<int, Bloc>
   */
  #[ORM\OneToMany(targetEntity: Bloc::class, mappedBy: 'secteur')]
  private Collection $blocs;

  public function __construct()
  {
    $this->blocs = new ArrayCollection();
  }

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getNom(): ?string
  {
    return $this->nom;
  }

  public function setNom(string $nom): static
  {
    $this->nom = $nom;

    return $this;
  }

  /**
   * @return Collection<int, Bloc>
   */
  public function getBlocs(): Collection
  {
    return $this->blocs;
  }

  public function addBloc(Bloc $bloc): static
  {
    if (!$this->blocs->contains($bloc)) {
      $this->blocs->add($bloc);
      $bloc->setSecteur($this);
    }

    return $this;
  }

  public function removeBloc(Bloc $bloc): static
  {
    if ($this->blocs->removeElement($bloc)) {
      // set the owning side to null (unless already changed)
      if ($bloc->getSecteur() === $this) {
        $bloc->setSecteur(null);
      }
    }

    return $this;
  }
}
