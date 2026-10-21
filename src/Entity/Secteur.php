<?php

namespace App\Entity;

use App\Repository\SecteurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SecteurRepository::class)]
#[UniqueEntity(fields: ['nom'], message: 'Un secteur porte déjà ce nom.')]
class Secteur
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  private ?int $id = null;

  #[ORM\Column(length: 255, unique: true)]
  #[Assert\NotBlank(message: 'Indiquez un nom.')]
  #[Assert\Length(max: 255)]
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

  public function explication(): string
  {
    return match ($this->nom) {
      'Dalle' => 'Le mur penche en arrière. Les pieds portent le corps : on cherche l’équilibre plus que la force.',
      'Pan' => 'Le mur est vertical. Le corps reste proche des prises, les mains et les pieds se partagent l’effort.',
      'Dévers' => 'Le mur penche au-dessus du grimpeur. Les bras et le gainage travaillent plus que sur un pan.',
      'Toit' => 'Le mur est presque à l’horizontale. On grimpe dessous : c’est le secteur le plus physique.',
      default => 'Une zone de la salle. Les blocs de ce secteur sont regroupés au même endroit du mur.',
    };
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
