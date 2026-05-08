<?php

namespace App\Form;

use App\Entity\ParticipationRestaurant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{
    IntegerType, TextareaType, CheckboxType
};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<ParticipationRestaurant>
 */
class ParticipationRestaurantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('menuPropositionId', IntegerType::class, [
                'label' => 'ID Menu',
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\PositiveOrZero(['message' => 'L\'ID du menu doit être positif'])
                ]
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('annule', CheckboxType::class, [
                'label' => 'Annulé',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ParticipationRestaurant::class,
        ]);
    }
}