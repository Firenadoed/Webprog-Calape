<?php

namespace App\Form;

use App\Entity\Collectible;
use App\Entity\Listing;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ListingType extends AbstractType
{
    private EntityManagerInterface $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('grade', ChoiceType::class, [
                'label' => 'PSA Grade',
                'choices' => [
                    'Unrated / Not Graded' => 'Unrated / Not Graded',
                    '10 - Gem Mint (GEM-MT)' => '10 - GEM-MT',
                    '9 - Mint' => '9 - MINT',
                    '8 - Near Mint–Mint (NM-MT)' => '8 - NM-MT',
                    '7 - Near Mint' => '7 - NM',
                    '6 - Excellent–Mint (EX-MT)' => '6 - EX-MT',
                    '5 - Excellent' => '5 - EX',
                    '4 - Very Good–Excellent' => '4 - VG-EX',
                    '3 - Very Good' => '3 - VG',
                    '2 - Good' => '2 - GOOD',
                    '1 - Poor' => '1 - POOR',
                ],
                'placeholder' => 'Select a grade',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Price (₱)',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter price',
                    'min' => 0,
                    'step' => 0.01,
                ],
            ])
            ->add('is_for_sale', ChoiceType::class, [
                'label' => 'Listing Status',
                'choices' => [
                    'For Sale' => true,
                    'Not for Sale' => false,
                ],
                'placeholder' => 'Select listing status',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    $displayName = $user->getUsername() ?: $user->getEmail();
                    return sprintf('%s (ID: %d)', $displayName, $user->getId());
                },
                'placeholder' => 'Select a user',
                'attr' => [
                    'class' => 'form-select user-select',
                ],
            ]);

        // Form modifier for collectible field - LOCKED when editing
        $formModifier = function (FormInterface $form, ?User $user = null, ?Listing $listing = null) {
            $isEditing = ($listing && $listing->getId() !== null);
            $isDisabled = $isEditing; // Disable when editing
            
            $form->add('collectible', EntityType::class, [
                'class' => Collectible::class,
                'choice_label' => function (Collectible $collectible) {
                    return sprintf('%s (ID: %d)', $collectible->getName(), $collectible->getId());
                },
                'placeholder' => $user 
                    ? sprintf('Select a collectible owned by %s', $user->getUsername() ?: $user->getEmail())
                    : 'Select a user first',
                'attr' => [
                    'class' => 'form-select collectible-select',
                    'readonly' => $isDisabled,
                    'disabled' => $isDisabled,
                ],
                'query_builder' => function (EntityRepository $er) use ($user, $listing, $isEditing) {
                    $qb = $er->createQueryBuilder('c');
                    
                    if ($isEditing && $listing && $listing->getCollectible()) {
                        // When editing, only show the currently selected collectible
                        return $qb
                            ->where('c.id = :collectibleId')
                            ->setParameter('collectibleId', $listing->getCollectible()->getId());
                    }
                    
                    if ($user) {
                        // When creating, show all collectibles owned by the user
                        return $qb
                            ->where('c.user = :user')
                            ->setParameter('user', $user)
                            ->orderBy('c.name', 'ASC');
                    }
                    
                    // Return empty query builder if no user selected
                    return $qb->where('1 = 0');
                },
                'required' => true,
                'disabled' => $isDisabled || !$user,
            ]);
        };

        // PRE_SET_DATA: When loading existing listing
     // In PRE_SET_DATA event
$builder->addEventListener(
    FormEvents::PRE_SET_DATA,
    function (FormEvent $event) use ($formModifier) {
        /** @var Listing $listing */
        $listing = $event->getData();
        $user = $listing ? $listing->getUser() : null;
        
        $isEditing = ($listing && $listing->getId() !== null);
        
        // Only add user field when NOT editing
        if (!$isEditing) {
            $event->getForm()->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    $displayName = $user->getUsername() ?: $user->getEmail();
                    return sprintf('%s (ID: %d)', $displayName, $user->getId());
                },
                'placeholder' => 'Select a user',
                'attr' => [
                    'class' => 'form-select user-select',
                ],
            ]);
        }
        
        $formModifier($event->getForm(), $user, $listing);
    }
);
        // PRE_SUBMIT: When form is submitted (handles AJAX updates)
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $listing = $event->getForm()->getData(); // Get existing listing
                $user = null;
                
                if (!empty($data['user'])) {
                    // Use the EntityManager injected in constructor
                    $user = $this->entityManager->getRepository(User::class)
                        ->find($data['user']);
                }
                
                $formModifier($event->getForm(), $user, $listing);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Listing::class,
        ]);
    }
}