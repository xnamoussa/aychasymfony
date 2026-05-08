<?php
// src/Form/RegistrationFormType.php
// This form is ONLY for the public register page.
// It is separate from UsersType (which is used by the admin CRUD).

namespace App\Form;

use App\Entity\Users;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;


use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

/**
 * @extends AbstractType<Users>
 */
class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => ['placeholder' => 'Jane Doe'],
            ])

            ->add('email', EmailType::class, [
                'attr' => ['placeholder' => 'jane@example.com'],
            ])

            ->add('phone', TelType::class, [
                'required' => false,
                'attr'     => ['placeholder' => '12345678'],
            ])

            ->add('motorized', ChoiceType::class, [
                'choices' => [
                    'Select' => null,
                    'Yes'    => 'YES',
                    'No'     => 'NO',
                ],
                'required' => false,
            ])

            // Plain password — NOT mapped to entity (we hash it manually in the controller)
            ->add('password', PasswordType::class, [
                'mapped' => false,
                'attr'   => ['placeholder' => 'Min. 6 characters'],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a password']),
                    new Length([
                        'min'        => 6,
                        'minMessage' => 'Password must be at least {{ limit }} characters',
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[A-Z])(?=.*[0-9])(?=.*[\W]).+$/',
                        'message' => 'Password must contain an uppercase letter, a number and a special character',
                    ]),
                ],
            ])

            // Image upload — NOT mapped to entity (we handle the file move in controller)
            ->add('imageFile', FileType::class, [
                'mapped'   => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize'          => '2M',
                        'mimeTypes'        => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPG, PNG or WEBP)',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Users::class,
        ]);
    }
}