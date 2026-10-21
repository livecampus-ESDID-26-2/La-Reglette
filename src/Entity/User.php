<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'utilisateur')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cette adresse email.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  private ?int $id = null;

  #[ORM\Column(length: 180)]
  #[Assert\NotBlank]
  #[Assert\Email]
  private ?string $email = null;

  /**
   * @var list<string> The user roles
   */
  #[ORM\Column]
  private array $roles = [];

  /**
   * @var string The hashed password
   */
  #[ORM\Column]
  private ?string $password = null;

  #[ORM\Column(length: 255)]
  #[Assert\NotBlank]
  #[Assert\Length(max: 255)]
  private ?string $prenom = null;

  #[ORM\Column(length: 255)]
  #[Assert\NotBlank]
  #[Assert\Length(max: 255)]
  private ?string $nom = null;

  /**
   * @var Collection<int, Bloc>
   */
  #[ORM\OneToMany(targetEntity: Bloc::class, mappedBy: 'ouvreur')]
  private Collection $blocsOuverts;

  /**
   * @var Collection<int, Ascension>
   */
  #[ORM\OneToMany(targetEntity: Ascension::class, mappedBy: 'grimpeur')]
  private Collection $ascensions;

  public function __construct()
  {
    $this->blocsOuverts = new ArrayCollection();
    $this->ascensions = new ArrayCollection();
  }

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getEmail(): ?string
  {
    return $this->email;
  }

  public function setEmail(string $email): static
  {
    $this->email = $email;

    return $this;
  }

  /**
   * A visual identifier that represents this user.
   *
   * @see UserInterface
   */
  public function getUserIdentifier(): string
  {
    return (string) $this->email;
  }

  /**
   * @see UserInterface
   */
  public function getRoles(): array
  {
    $roles = $this->roles;
    // guarantee every user at least has ROLE_USER
    $roles[] = 'ROLE_USER';

    return array_unique($roles);
  }

  /**
   * @param list<string> $roles
   */
  public function setRoles(array $roles): static
  {
    $this->roles = $roles;

    return $this;
  }

  public function getRolePrincipal(): string
  {
    return $this->roles[0] ?? 'ROLE_GRIMPEUR';
  }

  /**
   * @see PasswordAuthenticatedUserInterface
   */
  public function getPassword(): ?string
  {
    return $this->password;
  }

  public function setPassword(string $password): static
  {
    $this->password = $password;

    return $this;
  }

  /**
   * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
   */
  public function __serialize(): array
  {
    $data = (array) $this;
    $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);

    return $data;
  }

  #[\Deprecated]
  public function eraseCredentials(): void
  {
    // @deprecated, to be removed when upgrading to Symfony 8
  }

  public function getPrenom(): ?string
  {
    return $this->prenom;
  }

  public function setPrenom(string $prenom): static
  {
    $this->prenom = $prenom;

    return $this;
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
  public function getBlocsOuverts(): Collection
  {
    return $this->blocsOuverts;
  }

  public function addBlocsOuvert(Bloc $blocsOuvert): static
  {
    if (!$this->blocsOuverts->contains($blocsOuvert)) {
      $this->blocsOuverts->add($blocsOuvert);
      $blocsOuvert->setOuvreur($this);
    }

    return $this;
  }

  public function removeBlocsOuvert(Bloc $blocsOuvert): static
  {
    if ($this->blocsOuverts->removeElement($blocsOuvert)) {
      // set the owning side to null (unless already changed)
      if ($blocsOuvert->getOuvreur() === $this) {
        $blocsOuvert->setOuvreur(null);
      }
    }

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
      $ascension->setGrimpeur($this);
    }

    return $this;
  }

  public function removeAscension(Ascension $ascension): static
  {
    if ($this->ascensions->removeElement($ascension)) {
      // set the owning side to null (unless already changed)
      if ($ascension->getGrimpeur() === $this) {
        $ascension->setGrimpeur(null);
      }
    }

    return $this;
  }
}
