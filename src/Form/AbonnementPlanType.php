<?php
namespace App\Form;

use App\Entity\Abonnement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{TextType, TextareaType, ChoiceType, NumberType, CheckboxType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Abonnement>
 */
class AbonnementPlanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du Plan',
                'attr'  => ['placeholder' => 'Ex: Pack Mensuel Découverte'],
            ])
            ->add('type', ChoiceType::class, [
                'label'       => 'Type de Pack',
                'choices'     => [
                    'Mensuel'       => Abonnement::TYPE_MENSUEL,
                    'Annuel'        => Abonnement::TYPE_ANNUEL,
                    'Premium'       => Abonnement::TYPE_PREMIUM,
                    'Event Pass'    => Abonnement::TYPE_EVENT_PASS,
                ],
                'placeholder' => '-- Sélectionner un type --',
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix (€)',
                'scale' => 2,
                'attr'  => ['placeholder' => '0.00'],
            ])
            ->add('restrictionType', TextType::class, [
                'label'    => 'Restriction alimentaire (optionnel)',
                'required' => false,
                'attr'     => ['placeholder' => 'Ex: Végétarien, Sans gluten...'],
            ])
            ->add('autoRenew', CheckboxType::class, [
                'label'    => 'Renouvellement automatique',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Abonnement::class,
        ]);
    }
}
