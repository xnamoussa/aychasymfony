<?php

namespace App\Form;

use App\Entity\Ingredient;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{
    TextType, ChoiceType, NumberType, IntegerType, CheckboxType
};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<Ingredient>
 */
class IngredientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'Ingrédient',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est obligatoire']),
                    new Assert\Length(['max' => 255])
                ]
            ])
            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => array_combine(Ingredient::CATEGORIES, Ingredient::CATEGORIES),
                'placeholder' => '-- Sélectionner une catégorie --',
                'attr' => ['class' => 'form-select'],
                'constraints' => [new Assert\NotBlank(['message' => 'La catégorie est obligatoire'])]
            ])
            ->add('prixSupplement', NumberType::class, [
                'label' => 'Prix Supplémentaire (€)',
                'scale' => 2,
                'attr' => ['class' => 'form-control', 'min' => 0],
                'constraints' => [new Assert\NotBlank(['message' => 'Le prix est obligatoire'])]
            ])
            ->add('calories', IntegerType::class, [
                'label' => 'Calories',
                'required' => false,
                'attr' => ['class' => 'form-control', 'min' => 0]
            ])
            ->add('proteines', IntegerType::class, [
                'label' => 'Protéines (g)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'min' => 0]
            ])
            ->add('iconUrl', TextType::class, [
                'label' => 'URL de l\'icône',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('stockQuantite', IntegerType::class, [
                'label' => 'Quantité en Stock',
                'required' => false,
                'attr' => ['class' => 'form-control', 'min' => 0]
            ])
            ->add('stockSeuilAlerte', IntegerType::class, [
                'label' => 'Seuil d\'alerte',
                'required' => false,
                'attr' => ['class' => 'form-control', 'min' => 0]
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('repas', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'class' => \App\Entity\RepasDetaille::class,
                'choice_label' => 'nom',
                'label' => 'Plat Associé',
                'placeholder' => '-- Aucun plat associé --',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ingredient::class,
        ]);
    }
}