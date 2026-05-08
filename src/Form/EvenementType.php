<?php

namespace App\Form;

use App\Entity\Evenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Evenement>
 */
class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'required' => false,
                'attr' => [
                    'placeholder' => "Entrez le titre de l'événement",
                ],
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Décrivez votre événement',
                ],
            ])

            ->add('type', TextType::class, [
                'label' => 'Type',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: SOIREE, CAMPING, SEJOUR',
                ],
            ])

            ->add('date_debut', DateType::class, [
                'label' => 'Date début',
                'widget' => 'single_text',
                'required' => false,
            ])

            ->add('date_fin', DateType::class, [
                'label' => 'Date fin',
                'widget' => 'single_text',
                'required' => false,
            ])

            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Entrez le lieu',
                ],
            ])

            ->add('image', TextType::class, [
                'label' => "Image (URL ou IA)",
                'required' => false,
                'attr' => [
                    'placeholder' => "Ex: evenement.jpg ou base64 générée",
                ],
            ])
            ->add('imageFile', \Vich\UploaderBundle\Form\Type\VichImageType::class, [
                'label' => 'Upload Image (depuis votre ordinateur)',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => true,
                'image_uri' => true,
            ])

            ->add('spotify_url', UrlType::class, [
                'label' => 'Lien Spotify',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://open.spotify.com/...',
                ],
            ])

            ->add('recommended_equipments', TextType::class, [
                'mapped' => false,
                'label' => 'Équipements recommandés (séparés par des virgules)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: tente, sac de couchage, dress code...',
                    'id' => 'equipments-input',
                ],
            ])
            
            ->add('propose_makeup', CheckboxType::class, [
                'label' => 'Proposer un Coin Makeup ?',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
        ]);
    }
}