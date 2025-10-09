<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType; // 👈 make sure this is imported

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('price')
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Card' => 'card',
                    'Figures' => 'figures',
                    'Games' => 'games',
                ],
                'placeholder' => 'Select type',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('quality', ChoiceType::class, [
                'choices'  => [
                    'Mint' => 'mint',
                    'Near Mint' => 'near_mint',
                    'Good' => 'good',
                    'Fair' => 'fair',
                    'Poor' => 'poor',
                ],
                'placeholder' => 'Select quality',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('availability');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
