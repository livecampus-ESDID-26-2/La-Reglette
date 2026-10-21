<?php

namespace App\Security\Voter;

use App\Entity\Ascension;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<'ASCENSION_DELETE', Ascension>
 */
final class AscensionVoter extends Voter
{
  public const DELETE = 'ASCENSION_DELETE';

  protected function supports(string $attribute, mixed $subject): bool
  {
    return $attribute === self::DELETE && $subject instanceof Ascension;
  }

  protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
  {
    $user = $token->getUser();
    if (!($user instanceof User)) {
      return false;
    }

    return $subject->getGrimpeur()?->getId() === $user->getId();
  }
}
