<?php

namespace App\Form;

use App\Entity\Listing;
use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', ChoiceType::class, [
                'label' => 'Order Status',
                'choices' => [
                    'Pending' => 'pending',
                    'Confirmed' => 'confirmed', 
                    'Completed' => 'completed',
                    'Cancelled' => 'cancelled',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('buyer', EntityType::class, [
                'class' => User::class,
                'label' => 'Buyer',
                'choice_label' => function(User $user) {
                    return $user->getUsername() . ' (ID: ' . $user->getId() . ')';
                },
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Select a buyer',
            ]);

        // Conditional: Only show listing field when creating NEW order
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                /** @var Order $order */
                $order = $event->getData();
                $form = $event->getForm();
                
                // Only add listing field if order is NEW (has no ID)
                if ($order === null || $order->getId() === null) {
                    $form->add('listing', EntityType::class, [
                        'class' => Listing::class,
                        'label' => 'Listing',
                        'choice_label' => function(Listing $listing) {
                            $collectibleName = $listing->getCollectible() ? $listing->getCollectible()->getName() : 'Unknown';
                            return $collectibleName . ' - ₱' . $listing->getPrice() . ' (ID: ' . $listing->getId() . ')';
                        },
                        'attr' => ['class' => 'form-select'],
                        'placeholder' => 'Select a listing',
                        'choice_attr' => function(Listing $listing) {
                            // Add data-owner-id attribute for JavaScript filtering
                            return ['data-owner-id' => $listing->getUser()->getId()];
                        },
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('l')
                                ->where('l.is_for_sale = :forSale')
                                ->setParameter('forSale', true);
                        },
                    ]);
                }
            }
        );

        // Dynamically update listing choices based on selected buyer (only for new orders)
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();
            $order = $event->getForm()->getData(); // Get existing order
            
            // Only add listing field if this is a new order (no ID)
            $isNewOrder = ($order === null || $order->getId() === null);
            
            if ($isNewOrder && isset($data['buyer'])) {
                $buyerId = $data['buyer'];
                
                // Update listing field to exclude buyer's own listings
                $form->add('listing', EntityType::class, [
                    'class' => Listing::class,
                    'label' => 'Listing',
                    'choice_label' => function(Listing $listing) {
                        $collectibleName = $listing->getCollectible() ? $listing->getCollectible()->getName() : 'Unknown';
                        return $collectibleName . ' - ₱' . $listing->getPrice() . ' (ID: ' . $listing->getId() . ')';
                    },
                    'attr' => ['class' => 'form-select'],
                    'placeholder' => 'Select a listing',
                    'query_builder' => function (EntityRepository $er) use ($buyerId) {
                        return $er->createQueryBuilder('l')
                            ->where('l.is_for_sale = :forSale')
                            ->andWhere('l.user != :buyerId') // ← KEY: Exclude buyer's own listings
                            ->setParameter('forSale', true)
                            ->setParameter('buyerId', $buyerId);
                    },
                ]);
            }
        });

        // Auto-fill fields when form is submitted
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var Order $order */
            $order = $event->getData();
            
            if ($order->getListing()) {
                $listing = $order->getListing();
                $order->setCollectibleName($listing->getCollectible()->getName());
                $order->setPurchasePrice($listing->getPrice());
                $order->setSeller($listing->getUser());
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}