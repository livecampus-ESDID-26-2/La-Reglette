<?php

namespace App\Form;

use App\Entity\Bloc;
use App\Entity\Secteur;
use App\Enum\Cotation;
use App\Enum\CouleurPrise;
use App\Repository\SecteurRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Bloc>
 */
class BlocType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('nom', TextType::class, [
        'label' => 'Nom',
      ])
      ->add('couleur', EnumType::class, [
        'class' => CouleurPrise::class,
        'label' => 'Couleur des prises',
        'choice_label' => fn(CouleurPrise $couleur): string => $couleur->label(),
        'placeholder' => 'Choisir',
      ])
      ->add('cotation', EnumType::class, [
        'class' => Cotation::class,
        'label' => 'Cotation',
        'choice_label' => fn(Cotation $cotation): string => $cotation->label(),
        'placeholder' => 'Choisir',
      ])
      ->add('secteur', EntityType::class, [
        'class' => Secteur::class,
        'choice_label' => 'nom',
        'label' => 'Secteur',
        'placeholder' => 'Choisir',
        'query_builder' => fn(SecteurRepository $secteurs) => $secteurs
          ->createQueryBuilder('s')
          ->orderBy('s.nom', 'ASC'),
      ])
      ->add('dateOuverture', DateType::class, [
        'label' => 'Date d\'ouverture',
        'widget' => 'single_text',
        'input' => 'datetime_immutable',
      ]);
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
      'data_class' => Bloc::class,
    ]);
  }
}
