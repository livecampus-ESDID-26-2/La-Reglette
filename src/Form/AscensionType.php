<?php

namespace App\Form;

use App\Entity\Ascension;
use App\Entity\Bloc;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Ascension>
 */
class AscensionType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('dateReussite', DateType::class, [
        'label' => 'Date de réussite',
        'widget' => 'single_text',
        'input' => 'datetime_immutable',
      ])
      ->add('note', IntegerType::class, [
        'label' => 'Note, de 1 à 5',
        'attr' => ['min' => 1, 'max' => 5],
      ])
      ->add('commentaire', TextareaType::class, [
        'label' => 'Commentaire',
        'required' => false,
      ]);

    $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($options): void {
      $form = $event->getForm();
      $ascension = $event->getData();
      $bloc = $options['bloc'];

      if (!($ascension instanceof Ascension) || !($bloc instanceof Bloc)) {
        return;
      }

      if (!$bloc->isEnPlace()) {
        $form->addError(new FormError('Ce bloc est démonté.'));
      }

      $date = $ascension->getDateReussite();
      $ouverture = $bloc->getDateOuverture();
      if ($date === null || $ouverture === null) {
        return;
      }

      $jour = $date->format('Y-m-d');
      if ($jour < $ouverture->format('Y-m-d')) {
        $form->get('dateReussite')->addError(new FormError('La réussite ne peut pas précéder le jour d\'ouverture.'));
      }

      if ($jour > (new \DateTimeImmutable('today'))->format('Y-m-d')) {
        $form->get('dateReussite')->addError(new FormError('La réussite ne peut pas être dans le futur.'));
      }
    });
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
      'data_class' => Ascension::class,
    ]);
    $resolver->setRequired('bloc');
    $resolver->setAllowedTypes('bloc', Bloc::class);
  }
}
