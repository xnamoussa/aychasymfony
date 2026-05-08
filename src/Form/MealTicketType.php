<?php

namespace App\Form;

use App\Entity\MealTicket;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{
    TextType, ChoiceType, NumberType, DateTimeType
};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<MealTicket>
 */
class MealTicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ticketCode', TextType::class, [
                'label' => 'Code du Ticket Repas',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le code du ticket est obligatoire']),
                    new Assert\Length(['max' => 50])
                ]
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix (€)',
                'scale' => 2,
                'attr' => ['class' => 'form-control', 'min' => 0],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prix est obligatoire']),
                    new Assert\PositiveOrZero(['message' => 'Le prix doit être positif ou nul'])
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Actif' => 'Actif',
                    'Utilisé' => 'Utilisé',
                    'Annulé' => 'Annulé'
                ],
                'attr' => ['class' => 'form-select'],
                'placeholder' => '-- Sélectionner un statut --',
                'constraints' => [new Assert\NotBlank(['message' => 'Le statut est obligatoire'])]
            ])
            ->add('timeSlot', DateTimeType::class, [
                'label' => 'Plage Horaire',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'constraints' => [new Assert\NotBlank(['message' => 'La plage horaire est obligatoire'])]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MealTicket::class,
        ]);
    }
}