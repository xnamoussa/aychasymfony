<?php
namespace App\Form;

use App\Entity\ProgrammeRecommande;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{TextType, TextareaType, IntegerType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<ProgrammeRecommande>
 */
class ProgrammeRecommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du Programme',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('score', IntegerType::class, [
                'label' => 'Score de Recommandation (%)',
                'attr' => ['class' => 'form-control', 'min' => 0, 'max' => 100],
                'constraints' => [
                    new Assert\PositiveOrZero(),
                    new Assert\LessThanOrEqual(100),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProgrammeRecommande::class,
        ]);
    }
}