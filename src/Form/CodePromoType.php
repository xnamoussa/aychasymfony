<?php

namespace App\Form;

use App\Entity\CodePromo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<CodePromo>
 */
class CodePromoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'EX: WELCOME10'
                ],
                'label' => 'Code',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le code est obligatoire']),
                    new Assert\Length([
                        'max' => 20,
                        'maxMessage' => 'Le code ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('discountPercentage', IntegerType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 100,
                    'placeholder' => '10'
                ],
                'label' => 'Remise (%)',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La remise est obligatoire']),
                    new Assert\Range([
                        'min' => 0,
                        'max' => 100,
                        'notInRangeMessage' => 'La remise doit être entre {{ min }} et {{ max }} %'
                    ])
                ]
            ])
            ->add('expirationDate', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'label' => 'Date d\'expiration',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date d\'expiration est obligatoire']),
                ]
            ])
            ->add('usageLimit', IntegerType::class, [
                'attr' => ['class' => 'form-control', 'min' => 0, 'placeholder' => '100'],
                'label' => 'Limite d\'usage',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La limite d\'usage est obligatoire']),
                    new Assert\PositiveOrZero(['message' => 'La limite doit être un nombre positif ou nul'])
                ]
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Code Actif',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CodePromo::class,
        ]);
    }
}