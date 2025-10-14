<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username')
            ->add('bio')
            ->add('profile_image', FileType::class, [
                'label' => 'Profile Image (JPEG, PNG, WEBP, JFIF)',
                'mapped' => false, // not mapped to the entity property directly
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/jfif',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, WEBP, or JFIF)',
                    ])
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'hash_property_path' => 'password',
                'mapped' => false
            ])             
            ->add('email')
            ->add('role', ChoiceType::class, [
                'choices'  => [
                    'Admin' => 'admin',
                    'User' => 'user',
                ],
                'placeholder' => 'Select role',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
