<?php

namespace App\Form;

use App\Entity\Participation;
use App\Entity\Evenement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{
    TextareaType, ChoiceType, IntegerType
};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<Participation>
 */
class ParticipationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('evenement', EntityType::class, [
                'class' => Evenement::class,
                'choice_label' => 'titre',
                'label' => 'Événement',
                'placeholder' => '-- Sélectionner un événement --',
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => "L'événement est obligatoire."]),
                ],
                'attr' => ['class' => 'form-control', 'id' => 'participation_evenement'],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de participation',
                'choices' => array_combine(Participation::TYPES, Participation::TYPES),
                'placeholder' => '-- Choisir --',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('nbAdultes', IntegerType::class, [
                'label' => 'Nombre d\'adultes',
                'data' => 1,
                'attr' => ['class' => 'form-control calc-field', 'min' => 0],
                'constraints' => [new Assert\PositiveOrZero()],
            ])
            ->add('nbEnfants', IntegerType::class, [
                'label' => 'Nombre d\'enfants',
                'data' => 0,
                'required' => false,
                'attr' => ['class' => 'form-control calc-field', 'min' => 0],
                'constraints' => [new Assert\PositiveOrZero()],
            ])
            ->add('nbChiens', IntegerType::class, [
                'label' => 'Animaux',
                'data' => 0,
                'required' => false,
                'attr' => ['class' => 'form-control calc-field', 'min' => 0],
                'constraints' => [new Assert\PositiveOrZero()],
            ])
            ->add('hebergementNuits', IntegerType::class, [
                'label' => 'Nombre de nuits d\'hébergement',
                'data' => 0,
                'required' => false,
                'attr' => ['class' => 'form-control calc-field', 'min' => 0],
                'constraints' => [new Assert\PositiveOrZero()],
            ])
            ->add('contexteSocial', ChoiceType::class, [
                'label' => 'Contexte social',
                'choices' => array_combine(Participation::CONTEXTES, Participation::CONTEXTES),
                'placeholder' => '-- Choisir --',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('mealOption', ChoiceType::class, [
                'label' => 'Option repas',
                'choices' => array_combine(Participation::MEAL_OPTIONS, Participation::MEAL_OPTIONS),
                'required' => false,
                'placeholder' => '-- Choisir --',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('besoinsSpeciaux', TextareaType::class, [
                'label' => 'Besoins spéciaux',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('generateType', ChoiceType::class, [
                'label' => 'Générer un titre d\'accès',
                'choices' => [
                    'Ticket' => 'TICKET',
                    'Badge' => 'BADGE',
                    'Pass' => 'PASS',
                ],
                'mapped' => false,
                'required' => true,
                'placeholder' => '-- Choisir le type --',
                'attr' => ['class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participation::class,
        ]);
    }
}