<?php
namespace App\Form;

use App\Entity\Restaurant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{
    TextType, TextareaType, EmailType, NumberType, IntegerType, CheckboxType, UrlType
};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<Restaurant>
 */
class RestaurantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du Restaurant *',
                'attr'  => [
                    'class'       => 'form-control',
                    'placeholder' => 'Ex : Le Grand Bistrot',
                    'maxlength'   => 100,
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom du restaurant est obligatoire.']),
                    new Assert\Length([
                        'max'        => 100,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('adresse', TextType::class, [
                'label'    => 'Adresse Complète *',
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => 'Ex : 12 Rue de la Paix, Tunis',
                    'maxlength'   => 255,
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => "L'adresse est obligatoire."]),
                    new Assert\Length([
                        'max'        => 255,
                        'maxMessage' => "L'adresse ne peut pas dépasser {{ limit }} caractères.",
                    ]),
                ],
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone *',
                'attr'  => [
                    'class'       => 'form-control',
                    'placeholder' => 'Ex : +216 71 000 000',
                    'maxlength'   => 20,
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le téléphone est obligatoire.']),
                    new Assert\Regex([
                        'pattern' => '/^[\d\s\+\-\(\)\.]{6,20}$/',
                        'message' => 'Numéro de téléphone invalide (6-20 caractères, chiffres acceptés).',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label'    => 'Email Professionnel *',
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => 'contact@restaurant.tn',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => "L'email est obligatoire."]),
                    new Assert\Email(['message' => "L'adresse email « {{ value }} » est invalide."]),
                    new Assert\Length(['max' => 100]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'rows'        => 4,
                    'placeholder' => 'Décrivez l\'établissement (facultatif)...',
                ],
                'constraints' => [
                    new Assert\Length([
                        'max'        => 1000,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('imageUrl', TextType::class, [
                'label'    => 'URL de l\'image',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => 'https://example.com/image.jpg (facultatif)',
                ],
                'constraints' => [
                    new Assert\Length([
                        'max'        => 255,
                        'maxMessage' => "L'URL ne peut pas dépasser {{ limit }} caractères.",
                    ]),
                    new Assert\Url([
                        'message'   => "L'URL de l'image n'est pas valide (ex: https://example.com/img.jpg).",
                        'protocols' => ['http', 'https'],
                    ]),
                ],
            ])
            ->add('rating', NumberType::class, [
                'label' => 'Note (0 – 5) *',
                'scale' => 1,
                'attr'  => [
                    'class' => 'form-control',
                    'min'   => 0,
                    'max'   => 5,
                    'step'  => '0.1',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La note est obligatoire.']),
                    new Assert\Range([
                        'min'        => 0,
                        'max'        => 5,
                        'notInRangeMessage' => 'La note doit être comprise entre {{ min }} et {{ max }}.',
                    ]),
                ],
            ])
            ->add('latitude', NumberType::class, [
                'label'    => 'Latitude',
                'scale'    => 6,
                'required' => false,
                'attr'     => ['class' => 'form-control', 'placeholder' => 'Ex : 36.818'],
                'constraints' => [
                    new Assert\Range([
                        'min'        => -90,
                        'max'        => 90,
                        'notInRangeMessage' => 'La latitude doit être comprise entre {{ min }} et {{ max }}.',
                    ]),
                ],
            ])
            ->add('longitude', NumberType::class, [
                'label'    => 'Longitude',
                'scale'    => 6,
                'required' => false,
                'attr'     => ['class' => 'form-control', 'placeholder' => 'Ex : 10.165'],
                'constraints' => [
                    new Assert\Range([
                        'min'        => -180,
                        'max'        => 180,
                        'notInRangeMessage' => 'La longitude doit être comprise entre {{ min }} et {{ max }}.',
                    ]),
                ],
            ])
            ->add('nombrePlaces', IntegerType::class, [
                'label' => 'Nombre de places *',
                'attr'  => ['class' => 'form-control', 'min' => 0],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nombre de places est obligatoire.']),
                    new Assert\PositiveOrZero(['message' => 'Le nombre de places doit être positif ou nul.']),
                ],
            ])
            ->add('isOpen', CheckboxType::class, [
                'label'    => 'Ouvert actuellement',
                'required' => false,
                'attr'     => ['class' => 'form-check-input'],
            ])
            ->add('actif', CheckboxType::class, [
                'label'    => 'Établissement Actif',
                'required' => false,
                'attr'     => ['class' => 'form-check-input'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Restaurant::class]);
    }
}