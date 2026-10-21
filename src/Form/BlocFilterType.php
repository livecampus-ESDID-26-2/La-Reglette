<?php

namespace App\Form;

use App\Entity\Secteur;
use App\Enum\Cotation;
use App\Model\BlocFiltre;
use App\Repository\SecteurRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<BlocFiltre>
 */
class BlocFilterType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('secteur', EntityType::class, [
        'class' => Secteur::class,
        'choice_label' => 'nom',
        'label' => 'Secteur',
        'required' => false,
        'placeholder' => 'Tous les secteurs',
        'query_builder' => fn(SecteurRepository $secteurs) => $secteurs
          ->createQueryBuilder('s')
          ->orderBy('s.nom', 'ASC'),
      ])
      ->add('cotationMin', EnumType::class, [
        'class' => Cotation::class,
        'label' => 'De',
        'required' => false,
        'placeholder' => 'Toutes',
        'choice_label' => fn(Cotation $cotation): string => $cotation->label(),
      ])
      ->add('cotationMax', EnumType::class, [
        'class' => Cotation::class,
        'label' => 'À',
        'required' => false,
        'placeholder' => 'Toutes',
        'choice_label' => fn(Cotation $cotation): string => $cotation->label(),
      ]);

    $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
      $filtre = $event->getData();
      if (
        $filtre instanceof BlocFiltre &&
        $filtre->cotationMin instanceof Cotation &&
        $filtre->cotationMax instanceof Cotation &&
        $filtre->cotationMin->value > $filtre->cotationMax->value
      ) {
        $event->getForm()->addError(new FormError('La cotation minimale dépasse la cotation maximale.'));
      }
    });
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
      'data_class' => BlocFiltre::class,
      'method' => 'GET',
      'csrf_protection' => false,
    ]);
  }

  public function getBlockPrefix(): string
  {
    return '';
  }
}
