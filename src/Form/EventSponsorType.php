<?php

namespace App\Form;

use App\Entity\Evenement;
use App\Entity\EventSponsor;
use App\Entity\Sponsor;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

/**
 * @extends AbstractType<EventSponsor>
 */
class EventSponsorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('event', EntityType::class, [
                'class'        => Evenement::class,
                'choice_label' => 'titre',
                'placeholder'  => '-- Choisir un événement --',
                'label'        => 'Événement',
                'constraints'  => [
                    new NotNull(message: "L'événement est obligatoire"),
                ],
            ])
            ->add('sponsor', EntityType::class, [
                'class'        => Sponsor::class,
                'choice_label' => 'nom',
                'placeholder'  => '-- Choisir un sponsor --',
                'label'        => 'Sponsor',
                'constraints'  => [
                    new NotNull(message: "Le sponsor est obligatoire"),
                ],
                'query_builder' => function (\App\Repository\SponsorRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->where('s.statut = :statut')
                        ->setParameter('statut', 1)
                        ->orderBy('s.nom', 'ASC');
                },
            ])
            ->add('niveau', ChoiceType::class, [
                'choices'     => [
                    'Gold'       => 'GOLD',
                    'Silver'     => 'SILVER',
                    'Bronze'     => 'BRONZE',
                    'Partenaire' => 'PARTENAIRE',
                ],
                'placeholder' => '-- Choisir un niveau --',
                'label'       => 'Niveau',
                'constraints' => [
                    new NotBlank(message: "Le niveau est obligatoire"),
                ],
            ])
            ->add('montant', NumberType::class, [
                'label'       => 'Montant (DT)',
                'scale'       => 2,
                'constraints' => [
                    new NotBlank(message: "Le montant est obligatoire"),
                    new Positive(message: "Le montant doit être supérieur à 0"),
                    new LessThanOrEqual(value: 999999.99, message: "Le montant est trop élevé"),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventSponsor::class,
        ]);
    }
}
