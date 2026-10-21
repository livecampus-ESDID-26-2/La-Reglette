<?php

namespace App\Security\Voter;

use App\Entity\Bloc;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<'BLOC_EDIT'|'BLOC_DEMONTER', Bloc>
 */
final class BlocVoter extends Voter
{
  public const EDIT = 'BLOC_EDIT';
  public const DEMONTER = 'BLOC_DEMONTER';

  protected function supports(string $attribute, mixed $subject): bool
  {
    return in_array($attribute, [self::EDIT, self::DEMONTER], true) && $subject instanceof Bloc;
  }

  protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
  {
    $user = $token->getUser();
    if (!($user instanceof User)) {
      return false;
    }

    if (in_array('ROLE_ADMIN', $token->getRoleNames(), true)) {
      return true;
    }

    return in_array('ROLE_OUVREUR', $token->getRoleNames(), true) &&
      $subject->getOuvreur()?->getId() === $user->getId();
  }
}
