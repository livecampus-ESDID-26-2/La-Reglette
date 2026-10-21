<?php

namespace App\Entity;

use App\Enum\Cotation;
use App\Enum\CouleurPrise;
use App\Repository\BlocRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BlocRepository::class)]
#[ORM\Index(columns: ['date_demontage', 'cotation'])]
#[ORM\Index(columns: ['secteur_id'])]
#[ORM\Index(columns: ['ouvreur_id'])]
class Bloc
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  private ?int $id = null;

  #[ORM\Column(length: 255)]
  private ?string $nom = null;

  #[ORM\Column(enumType: CouleurPrise::class)]
  private ?CouleurPrise $couleur = null;

  #[ORM\Column(enumType: Cotation::class)]
  private ?Cotation $cotation = null;

  #[ORM\Column]
  private ?\DateTimeImmutable $dateOuverture = null;

  #[ORM\Column(nullable: true)]
  private ?\DateTimeImmutable $dateDemontage = null;

  #[ORM\Column(options: ['default' => 0])]
  private int $nombreReussites = 0;

  #[ORM\Column(nullable: true)]
  private ?float $noteMoyenne = null;

  #[ORM\ManyToOne(inversedBy: 'blocs')]
  #[ORM\JoinColumn(nullable: false)]
  private ?Secteur $secteur = null;

  #[ORM\ManyToOne(inversedBy: 'blocsOuverts')]
  #[ORM\JoinColumn(nullable: false)]
  private ?User $ouvreur = null;

  /**
   * @var Collection<int, Ascension>
   */
  #[ORM\OneToMany(targetEntity: Ascension::class, mappedBy: 'bloc')]
  private Collection $ascensions;

  public function __construct()
  {
    $this->ascensions = new ArrayCollection();
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

  public function getCouleur(): ?CouleurPrise
  {
    return $this->couleur;
  }

  public function setCouleur(CouleurPrise $couleur): static
  {
    $this->couleur = $couleur;

    return $this;
  }

  public function getCotation(): ?Cotation
  {
    return $this->cotation;
  }

  public function setCotation(Cotation $cotation): static
  {
    $this->cotation = $cotation;

    return $this;
  }

  public function getDateOuverture(): ?\DateTimeImmutable
  {
    return $this->dateOuverture;
  }

  public function setDateOuverture(\DateTimeImmutable $dateOuverture): static
  {
    $this->dateOuverture = $dateOuverture;

    return $this;
  }

  public function getDateDemontage(): ?\DateTimeImmutable
  {
    return $this->dateDemontage;
  }

  public function setDateDemontage(?\DateTimeImmutable $dateDemontage): static
  {
    $this->dateDemontage = $dateDemontage;

    return $this;
  }

  public function getNombreReussites(): int
  {
    return $this->nombreReussites;
  }

  public function setNombreReussites(int $nombreReussites): static
  {
    $this->nombreReussites = $nombreReussites;

    return $this;
  }

  public function getNoteMoyenne(): ?float
  {
    return $this->noteMoyenne;
  }

  public function setNoteMoyenne(?float $noteMoyenne): static
  {
    $this->noteMoyenne = $noteMoyenne;

    return $this;
  }

  public function getSecteur(): ?Secteur
  {
    return $this->secteur;
  }

  public function setSecteur(?Secteur $secteur): static
  {
    $this->secteur = $secteur;

    return $this;
  }

  public function getOuvreur(): ?User
  {
    return $this->ouvreur;
  }

  public function setOuvreur(?User $ouvreur): static
  {
    $this->ouvreur = $ouvreur;

    return $this;
  }

  /**
   * @return Collection<int, Ascension>
   */
  public function getAscensions(): Collection
  {
    return $this->ascensions;
  }

  public function addAscension(Ascension $ascension): static
  {
    if (!$this->ascensions->contains($ascension)) {
      $this->ascensions->add($ascension);
      $ascension->setBloc($this);
    }

    return $this;
  }

  public function removeAscension(Ascension $ascension): static
  {
    if ($this->ascensions->removeElement($ascension)) {
      // set the owning side to null (unless already changed)
      if ($ascension->getBloc() === $this) {
        $ascension->setBloc(null);
      }
    }

    return $this;
  }
}
