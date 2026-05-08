<?php

namespace App\Form;

use App\Entity\Menu;
use App\Entity\Restaurant;
use App\Repository\RestaurantRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{
    TextType, TextareaType, NumberType, DateType, CheckboxType, ChoiceType
};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<Menu>
 */
class MenuType extends AbstractType
{
    private RestaurantRepository $restaurantRepo;

    public function __construct(RestaurantRepository $restaurantRepo)
    {
        $this->restaurantRepo = $restaurantRepo;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Construire la liste des restaurants pour le select
        $restaurants = $this->restaurantRepo->findBy(['actif' => true], ['nom' => 'ASC']);
        $choices = [];
        foreach ($restaurants as $r) {
            $choices[$r->getNom()] = $r->getId();
        }

        $builder
            ->add('restaurantId', ChoiceType::class, [
                'label'       => 'Restaurant *',
                'choices'     => $choices,
                'placeholder' => '-- Sélectionner un restaurant --',
                'attr'        => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le restaurant est obligatoire.']),
                ],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom du Menu *',
                'attr'  => [
                    'class'       => 'form-control',
                    'placeholder' => 'Ex : Menu Scout Complet',
                    'maxlength'   => 100,
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom du menu est obligatoire.']),
                    new Assert\Length([
                        'max'        => 100,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'rows'        => 3,
                    'placeholder' => 'Description du menu (facultatif)...',
                ],
                'constraints' => [
                    new Assert\Length([
                        'max'        => 500,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Tarif (€) *',
                'scale' => 2,
                'attr'  => ['class' => 'form-control', 'min' => 0, 'step' => '0.01'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prix est obligatoire.']),
                    new Assert\PositiveOrZero(['message' => 'Le prix doit être positif ou nul.']),
                ],
            ])
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'label'  => 'Date Activation *',
                'attr'   => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de début est obligatoire.']),
                ],
            ])
            ->add('dateFin', DateType::class, [
                'widget' => 'single_text',
                'label'  => 'Date Expiration *',
                'attr'   => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de fin est obligatoire.']),
                ],
            ])
            ->add('actif', CheckboxType::class, [
                'label'    => 'Visible',
                'required' => false,
                'attr'     => ['class' => 'form-check-input'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Menu::class,
        ]);
    }
}