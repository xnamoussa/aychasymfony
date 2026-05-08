<?php
namespace App\Form;

use App\Entity\Abonnement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{TextType, ChoiceType, NumberType, DateType, CheckboxType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Abonnement>
 */
class AbonnementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, ['label' => 'Nom de l\'Abonnement'])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => array_combine(Abonnement::TYPES, Abonnement::TYPES),
                'placeholder' => '-- Sélectionner un type --',
            ])
            ->add('prix', NumberType::class, ['label' => 'Prix (€)', 'scale' => 2])
            ->add('dateDebut', DateType::class, ['label' => 'Date de Début', 'widget' => 'single_text'])
            ->add('dateFin', DateType::class, [
                'label' => 'Date de Fin',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => array_combine(Abonnement::STATUTS, Abonnement::STATUTS),
                'placeholder' => '-- Sélectionner un statut --',
            ])
            ->add('autoRenew', CheckboxType::class, [
                'label' => 'Renouvellement Automatique',
                'required' => false,
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
            'data_class' => Abonnement::class,
        ]);
    }
}