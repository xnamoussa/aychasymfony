<?php

namespace App\Form;

use App\Entity\Delivery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Delivery>
 */
class DeliveryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('estimation', DateTimeType::class, [
                'label' => 'Estimation de livraison',
                'widget' => 'single_text',
                'required' => false,
                'disabled' => !$options['status_editable'],
            ])
            ->add('dateLivraison', DateTimeType::class, [
                'label' => 'Date de livraison',
                'widget' => 'single_text',
                'required' => false,
                'disabled' => !$options['status_editable'],
            ])
            ->add('rue', TextType::class, [
                'label' => 'Rue',
                'required' => false,
                'disabled' => !$options['address_editable'],
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'disabled' => !$options['address_editable'],
            ])
            ->add('codePostal', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
                'disabled' => !$options['address_editable'],
            ])
            ->add('pays', TextType::class, [
                'label' => 'Pays',
                'required' => false,
                'disabled' => !$options['address_editable'],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut de livraison',
                'choices' => [
                    'En attente de confirmation' => 'en_attente',
                    'Confirmée' => 'confirmee',
                    'En préparation' => 'preparation',
                    'Expédiée' => 'expediee',
                    'En cours de livraison' => 'en_cours',
                    'Livrée' => 'livree',
                    'Annulée' => 'annulee',
                ],
                'disabled' => !$options['status_editable'],
            ])
            ->add('latitude', \Symfony\Component\Form\Extension\Core\Type\HiddenType::class, [
                'required' => false,
            ])
            ->add('longitude', \Symfony\Component\Form\Extension\Core\Type\HiddenType::class, [
                'required' => false,
            ])
            ->add('fraisLivraison', \Symfony\Component\Form\Extension\Core\Type\HiddenType::class, [
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Delivery::class,
            'status_editable' => true,
            'address_editable' => true,
        ]);
        $resolver->setAllowedTypes('status_editable', 'bool');
        $resolver->setAllowedTypes('address_editable', 'bool');
    }
}
