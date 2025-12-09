<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Username',
                'required' => true,
                'attr' => [
                    'class' => 'form-input-dark',
                    'placeholder' => 'Enter username',
                    'autocomplete' => 'username',
                    'minlength' => 3,
                    'maxlength' => 50,
                ],
                'help' => 'Username must be between 3 and 50 characters',
                'help_attr' => ['class' => 'form-help-text'],
            ]);
            
        // Add password field conditionally - NOT mapped to entity
        if (!$options['is_edit']) {
            // For new users - password is required
            $builder->add('password', PasswordType::class, [
                'label' => 'Password',
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'class' => 'form-input-dark',
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Enter password',
                    'minlength' => 6,
                ],
                'help' => 'Password must be at least 6 characters long',
                'help_attr' => ['class' => 'form-help-text'],
            ]);
        } else {
            // For editing - optional password field
            $builder->add('password', PasswordType::class, [
                'label' => 'New Password (leave blank to keep current)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-input-dark',
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Enter new password or leave blank',
                    'minlength' => 6,
                ],
                'help' => 'Leave blank to keep current password, or enter at least 6 characters',
                'help_attr' => ['class' => 'form-help-text'],
                'empty_data' => '',
            ]);
        }
        
        $builder
            ->add('Status', ChoiceType::class, [
                'choices' => [
                    'Active' => true,
                    'Inactive' => false,
                ],
                'label' => 'Status',
                'required' => true,
                'attr' => [
                    'class' => 'form-input-dark',
                ],
                'empty_data' => true,
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'User' => 'ROLE_USER',
                    'Staff' => 'ROLE_STAFF',
                    'Admin' => 'ROLE_ADMIN',
                ],
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'label' => 'Role',
                'attr' => [
                    'class' => 'gold-select',
                ],
                'mapped' => false,
                'empty_data' => 'ROLE_USER',
            ])
            ->add('profile_image', FileType::class, [
                'label' => 'Profile Image',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => 'image/*',
                    'class' => 'file-input'
                ],
                'help' => 'Allowed formats: JPG, PNG, GIF, WebP. Max size: 2MB',
                'help_attr' => ['class' => 'form-help-text'],
                'invalid_message' => 'Please upload a valid image file (JPG, PNG, GIF or WebP, max 2MB)',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
            'extra_fields_message' => 'This form contains invalid fields',
            'invalid_message' => 'The form contains invalid values. Please check the fields below.',
        ]);
        
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}