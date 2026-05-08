<?php
namespace App\Form;

use App\Entity\RepasDetaille;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{TextType, TextareaType, ChoiceType, NumberType, IntegerType, CheckboxType, FileType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<RepasDetaille>
 */
class RepasDetailleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du Plat',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix (€)',
                'scale' => 2,
                'attr' => ['class' => 'form-control', 'min' => 0],
                'constraints' => [new Assert\PositiveOrZero()],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description Complexe',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('calories', IntegerType::class, [
                'label' => 'Calories',
                'required' => false,
                'attr' => ['class' => 'form-control', 'min' => 0],
                'constraints' => [new Assert\PositiveOrZero()],
            ])
            ->add('typeRepas', ChoiceType::class, [
                'label' => 'Type de Repas',
                'choices' => array_combine(RepasDetaille::TYPES_REPAS, RepasDetaille::TYPES_REPAS),
                'placeholder' => '-- Choisir --',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('imageUrl', FileType::class, [
                'label' => 'Image du Plat',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG, WEBP)',
                    ])
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes Internes',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('vegetarien', CheckboxType::class, [
                'label' => 'Végétarien',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
            ->add('vegan', CheckboxType::class, [
                'label' => 'Vegan',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
            ->add('sansGluten', CheckboxType::class, [
                'label' => 'Sans gluten',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
            ->add('halal', CheckboxType::class, [
                'label' => 'Halal',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Plat Actif',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RepasDetaille::class,
            'allow_extra_fields' => true
        ]);
    }
}