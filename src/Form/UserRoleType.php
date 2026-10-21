<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<null>
 */
class UserRoleType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder->add('role', ChoiceType::class, [
      'label' => 'Rôle',
      'choices' => [
        'Grimpeur' => 'ROLE_GRIMPEUR',
        'Ouvreur' => 'ROLE_OUVREUR',
        'Admin' => 'ROLE_ADMIN',
      ],
      'data' => $options['role'],
    ]);
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
      'role' => 'ROLE_GRIMPEUR',
    ]);
    $resolver->setAllowedTypes('role', 'string');
  }
}
