<?php
namespace App\Form;

use App\Entity\Ticket;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{
    TextType, ChoiceType, NumberType, IntegerType, DateTimeType
};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Ticket>
 */
class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type de Ticket',
                'choices' => ['Standard' => 'Standard', 'VIP' => 'VIP', 'Étudiant' => 'Étudiant'],
                'placeholder' => '-- Choisir un type --',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('codeUnique', TextType::class, [
                'label' => 'Code Unique du Ticket',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('latitude', NumberType::class, [
                'label' => 'Latitude',
                'scale' => 6,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('longitude', NumberType::class, [
                'label' => 'Longitude',
                'scale' => 6,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu de Validité',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => ['Valide' => 'Valide', 'Utilisé' => 'Utilisé', 'Expiré' => 'Expiré'],
                'placeholder' => '-- Choisir un statut --',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('format', ChoiceType::class, [
                'label' => 'Format',
                'choices' => ['Numérique' => 'Numérique', 'Papier' => 'Papier'],
                'placeholder' => '-- Choisir un format --',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('userId', IntegerType::class, [
                'label' => 'ID Utilisateur',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateCreation', DateTimeType::class, [
                'label' => 'Date de Création',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateExpiration', DateTimeType::class, [
                'label' => 'Date d\'Expiration',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Ticket::class]);
    }
}