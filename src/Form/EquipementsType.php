<?php

namespace App\Form;

use App\Entity\Equipements;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Equipements>
 */
class EquipementsType extends AbstractType
{
    public const CATEGORIES = [
        'Tente', 'Matelas', 'Lunettes', 'Sac de couchage', 'Lampe', 'Réchaud', 'Chaise', 'Glacière', 'Autre',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, ['label' => 'Nom'])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => array_combine(self::CATEGORIES, self::CATEGORIES),
                'placeholder' => 'Choisir…',
                'required' => false,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => ['VENTE' => 'VENTE', 'LOCATION' => 'LOCATION'],
                'placeholder' => 'Choisir…',
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix (TND)',
                'scale' => 2,
                'html5' => true,
            ])
            ->add('ville', TextType::class, ['label' => 'Ville', 'required' => false])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'DISPONIBLE' => 'DISPONIBLE',
                    'VENDU' => 'VENDU',
                    'LOUE' => 'LOUE',
                ],
            ])
            ->add('livrable', ChoiceType::class, [
                'label' => 'Livrable',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('mail', EmailType::class, [
                'label' => 'E-mail (notification / contact)',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Equipements::class,
        ]);
    }
}
