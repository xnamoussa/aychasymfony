<?php
namespace App\Form;

use App\Entity\Restauration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{
    TextType, TextareaType, ChoiceType, TimeType, IntegerType, DateType, CheckboxType, NumberType
};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Restauration>
 */
class RestaurationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Catégorie Stream',
                'choices' => array_combine(Restauration::TYPES, Restauration::TYPES),
                'placeholder' => '-- Choisir un type --',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom Menu',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('libelle', TextType::class, [
                'label' => 'Libelle Option',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('nomRepas', TextType::class, [
                'label' => 'Nom Repas',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix',
                'required' => false,
                'scale' => 2,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('restrictionLibelle', TextType::class, [
                'label' => 'Restriction',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('restrictionDescription', TextareaType::class, [
                'label' => 'Description restriction',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('datePresence', DateType::class, [
                'label' => 'Date présence',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('abonnementActif', CheckboxType::class, [
                'label' => 'Abonnement actif',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
            ->add('participantId', IntegerType::class, [
                'label' => 'ID Participant',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Restauration::class]);
    }
}