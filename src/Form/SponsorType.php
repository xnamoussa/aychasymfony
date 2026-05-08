<?php

namespace App\Form;

use App\Entity\Sponsor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/**
 * @extends AbstractType<Sponsor>
 */
class SponsorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr'  => ['placeholder' => 'Nom du sponsor']
            ])
            ->add('numLocal', TextType::class, [
                'label'    => 'Téléphone',
                'mapped'   => false,
                'required' => true,
                'attr'     => [
                    'placeholder' => '12345678',
                    'maxlength'   => 8,
                ],
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank(message: 'Le téléphone est obligatoire'),
                    new \Symfony\Component\Validator\Constraints\Regex(
                        pattern: '/^[0-9]{8}$/',
                        message: 'Le téléphone doit contenir exactement 8 chiffres'
                    ),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr'  => ['placeholder' => 'email@exemple.com']
            ])
            ->add('logoFile', FileType::class, [
                'label'    => 'Logo',
                'mapped'   => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize'   => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG, GIF, WEBP)',
                    ])
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'label'   => 'Statut',
                'choices' => [
                    'Actif'   => true,
                    'Inactif' => false,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sponsor::class,
        ]);
    }
}