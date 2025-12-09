<?php

namespace App\Form;

use App\Entity\Collectible;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddCollectibleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Collectible Name',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter collectible name',
                    'minlength' => 2,
                    'maxlength' => 255,
                ],
                'help' => 'Name must be between 2 and 255 characters.',
                'help_attr' => [
                    'class' => 'help-text'
                ],
                'invalid_message' => 'Please enter a valid name (2-255 characters).',
                'empty_data' => '',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter description',
                    'rows' => 5,
                    'minlength' => 10,
                ],
                'help' => 'Description must be at least 10 characters long.',
                'help_attr' => [
                    'class' => 'help-text'
                ],
                'invalid_message' => 'Description must be at least 10 characters long.',
                'empty_data' => '',
            ])
            ->add('image', FileType::class, [
                'label' => 'Image',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*',
                ],
                'help' => 'Maximum file size: 5MB. Allowed formats: JPEG, PNG, GIF, WebP.',
                'help_attr' => [
                    'class' => 'help-text'
                ],
                'invalid_message' => 'Please upload a valid image (JPEG, PNG, GIF, WebP) up to 5MB.',
            ])
            ->add('franchise', TextType::class, [
                'label' => 'Franchise/Series',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'e.g., Pokemon, Marvel, etc.',
                    'maxlength' => 255,
                ],
                'help' => 'Enter the franchise or series name.',
                'help_attr' => [
                    'class' => 'help-text'
                ],
                'invalid_message' => 'Please enter a valid franchise name.',
                'empty_data' => '',
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'required' => true,
                'choices' => [
                    'Cards' => 'cards',
                    'Games' => 'games',
                    'Figures' => 'figures',
                    'Artworks' => 'artworks',
                ],
                'placeholder' => 'Select category',
                'attr' => [
                    'class' => 'form-control',
                ],
                'help' => 'Select the collectible category.',
                'help_attr' => [
                    'class' => 'help-text'
                ],
                'invalid_message' => 'Please select a valid category.',
                'empty_data' => '',
            ]);
          
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Collectible::class,
            'empty_data' => null,
            'invalid_message' => 'The form contains invalid values. Please check the fields below.',
            'extra_fields_message' => 'This form contains invalid fields.',
        ]);
    }
}