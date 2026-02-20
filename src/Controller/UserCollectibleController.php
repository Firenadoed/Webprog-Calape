<?php

namespace App\Controller;

use App\Entity\Collectible;
use App\Form\AddCollectibleType;
use App\Entity\Listing;
use App\Entity\Order;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/my-collection')]
final class UserCollectibleController extends AbstractController
{
    #[Route('/edit/{id}', name: 'collection_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Collectible $collectible, 
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        ActivityLogger $logger
    ): Response
    {
        // Only owner can edit their collectible
        if ($collectible->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'You can only edit your own collectibles!');
            return $this->redirectToRoute('collection');
        }

        $form = $this->createForm(AddCollectibleType::class, $collectible);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $collectibleImageFile = $form->get('image')->getData();
            if ($collectibleImageFile) {
                $originalFilename = pathinfo($collectibleImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$collectibleImageFile->guessExtension();

                try {
                    $collectibleImageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/collectibles',
                        $newFilename
                    );
                    
                    // Delete old image if not default
                    $oldImage = $collectible->getImage();
                    if ($oldImage && $oldImage !== 'default_collectible.jpg') {
                        $oldImagePath = $this->getParameter('kernel.project_dir').'/public/uploads/collectibles/'.$oldImage;
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    
                    $collectible->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                }
            }

            $entityManager->flush();

            // Log the activity
            $currentUser = $this->getUser();
            $logger->log(
                $currentUser, 
                'UPDATE_COLLECTIBLE',
                'Updated collectible: ' . $collectible->getName() . ' (ID: ' . $collectible->getId() . ')'
            );

            $this->addFlash('success', 'Collectible updated successfully!');
            return $this->redirectToRoute('collection');
        }

        return $this->render('collectible/user_edit.html.twig', [
            'collectible' => $collectible,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: 'collection_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        Collectible $collectible, 
        EntityManagerInterface $entityManager,
        ActivityLogger $logger
    ): Response
    {
        // Only owner can delete their collectible
        if ($collectible->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'You can only delete your own collectibles!');
            return $this->redirectToRoute('collection');
        }

        if ($this->isCsrfTokenValid('delete'.$collectible->getId(), $request->getPayload()->getString('_token'))) {
            // Log before deletion
            $currentUser = $this->getUser();
            $logger->log(
                $currentUser,
                'DELETE_COLLECTIBLE',
                'Deleted collectible: ' . $collectible->getName() . ' (ID: ' . $collectible->getId() . ')'
            );
            
            // Delete image file if not default
            $collectibleImage = $collectible->getImage();
            if ($collectibleImage && $collectibleImage !== 'default_collectible.jpg') {
                $imagePath = $this->getParameter('kernel.project_dir').'/public/uploads/collectibles/'.$collectibleImage;
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            $entityManager->remove($collectible);
            $entityManager->flush();
            
            $this->addFlash('success', 'Collectible deleted successfully!');
        }

        return $this->redirectToRoute('collection');
    }

    #[Route('/buy/{id}', name: 'order_buy', methods: ['POST'])]
    public function buy(
        Request $request, 
        Listing $listing, 
        EntityManagerInterface $entityManager,
        ActivityLogger $logger
    ): Response
    {
        // Only authenticated users can buy
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('buy' . $listing->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('listing_show', ['id' => $listing->getId()]);
        }
        
        $currentUser = $this->getUser();
        
        // Check if user is trying to buy their own listing
        if ($listing->getUser()->getId() === $currentUser->getId()) {
            $this->addFlash('error', 'You cannot purchase your own listing!');
            return $this->redirectToRoute('listing_show', ['id' => $listing->getId()]);
        }
        
        // Check if listing is still for sale
        if (!$listing->isForSale()) {
            $this->addFlash('error', 'This listing is no longer available for sale!');
            return $this->redirectToRoute('explore');
        }
        
        // Create order
        $order = Order::createFromListing($currentUser, $listing);
        $order->setStatus('pending'); // Regular user purchases are pending until confirmed by staff
        $order->setCreatedBy($currentUser); // Regular user is the creator of their own order
        
        // Hide the listing
        $listing->setIsForSale(false);
        
        $entityManager->persist($order);
        $entityManager->flush();
        
        // Log the purchase
        $logger->log(
            $currentUser,
            'CREATE_ORDER',
            'User purchased listing: ' . $listing->getCollectible()->getName() . 
            ' (Listing ID: ' . $listing->getId() . ', Order ID: ' . $order->getId() . ') - ' .
            'Price: ₱' . number_format($listing->getPrice(), 2)
        );
        
        $this->addFlash('success', 'Purchase successful! Your order is pending confirmation from our staff.');
        return $this->redirectToRoute('collection');
    }

    #[Route('/{id}/confirm', name: 'order_confirm', methods: ['POST'])]
    public function confirm(Order $order, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        if (!$this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Access denied!');
            return $this->redirectToRoute('app_order_index');
        }

        $order->setStatus('confirmed');
        $entityManager->flush();

        $currentUser = $this->getUser();
        $logger->log(
            $currentUser,
            'ORDER_UPDATE',
            'Confirmed order: ' . $order->getCollectibleName() . ' (ID: ' . $order->getId() . ')'
        );

        $this->addFlash('success', 'Order confirmed successfully.');
        return $this->redirectToRoute('app_order_index');
    }

    // USER-SPECIFIC QUICK ACTIONS (for sellers only)
    #[Route('/{id}/user-confirm', name: 'order_user_confirm', methods: ['POST'])]
    public function userConfirm(Order $order, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        // Check if current user is the seller of this order
        $currentUser = $this->getUser();
        $isSeller = $order->getSeller() && $order->getSeller()->getId() === $currentUser->getId();

        if (!$isSeller) {
            $this->addFlash('error', 'Only the seller can confirm this order!');
            return $this->redirectToRoute('my_listings');
        }

        $order->setStatus('confirmed');
        $entityManager->flush();

        $logger->log(
            $currentUser,
            'ORDER_UPDATE',
            'Seller confirmed order: ' . $order->getCollectibleName() . ' (ID: ' . $order->getId() . ')'
        );

        $this->addFlash('success', 'Order confirmed successfully.');
        return $this->redirectToRoute('my_listings');
    }

    #[Route('/{id}/complete', name: 'order_complete', methods: ['POST'])]
    public function complete(Order $order, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        if (!$this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Access denied!');
            return $this->redirectToRoute('app_order_index');
        }

        // Get the listing and collectible
        $listing = $order->getListing();
        
        if (!$listing) {
            $this->addFlash('error', 'Listing not found for this order!');
            return $this->redirectToRoute('app_order_index');
        }
        
        $collectible = $listing->getCollectible();
        
        if (!$collectible) {
            $this->addFlash('error', 'Collectible not found for this listing!');
            return $this->redirectToRoute('app_order_index');
        }
        
        // 1. Transfer collectible ownership to buyer
        $collectible->setUser($order->getBuyer());
        
        // 2. Remove the listing reference from the order
        $order->setListing(null);
        
        // 3. Delete the listing
        $entityManager->remove($listing);
        
        // 4. Update order status
        $order->setStatus('completed');
        
        $entityManager->flush();

        // Log the activity
        $currentUser = $this->getUser();
        $logger->log(
            $currentUser,
            'ORDER_UPDATE',
            'Completed order: ' . $order->getCollectibleName() . 
            ' (ID: ' . $order->getId() . '). ' .
            'Transferred to buyer: ' . $order->getBuyer()->getUsername() .
            ' and deleted listing ID: ' . $listing->getId()
        );

        $this->addFlash('success', 'Order completed! Collectible transferred to buyer and listing removed.');
        return $this->redirectToRoute('app_order_index');
    }

    #[Route('/{id}/cancel', name: 'order_cancel', methods: ['POST'])]
    public function cancel(Order $order, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        if (!$this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Access denied!');
            return $this->redirectToRoute('app_order_index');
        }

        $order->setStatus('cancelled');
        
        // Make listing available again when cancelled (if listing still exists)
        if ($order->getListing()) {
            $order->getListing()->setIsForSale(true);
        }
        
        $entityManager->flush();

        $currentUser = $this->getUser();
        $logger->log(
            $currentUser,
            'ORDER_UPDATE',
            'Cancelled order: ' . $order->getCollectibleName() . ' (ID: ' . $order->getId() . ')'
        );

        $this->addFlash('warning', 'Order cancelled.' . 
            ($order->getListing() ? ' Listing is available for sale again.' : ''));
        return $this->redirectToRoute('app_order_index');
    }

    #[Route('/{id}/user-cancel', name: 'order_user_cancel', methods: ['POST'])]
    public function userCancel(Order $order, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        // Check if current user is the seller of this order
        $currentUser = $this->getUser();
        $isSeller = $order->getSeller() && $order->getSeller()->getId() === $currentUser->getId();

        if (!$isSeller) {
            $this->addFlash('error', 'Only the seller can cancel this order!');
            return $this->redirectToRoute('my_listings');
        }

        $order->setStatus('cancelled');
        
        // Make listing available again when cancelled (if listing still exists)
        if ($order->getListing()) {
            $order->getListing()->setIsForSale(true);
        }
        
        $entityManager->flush();

        $logger->log(
            $currentUser,
            'ORDER_UPDATE',
            'Seller cancelled order: ' . $order->getCollectibleName() . ' (ID: ' . $order->getId() . ')'
        );

        $this->addFlash('warning', 'Order cancelled.' . 
            ($order->getListing() ? ' Listing is available for sale again.' : ''));
        return $this->redirectToRoute('my_listings');
    }

    #[Route('/{id}/user-cancel-purchase', name: 'order_user_cancel_purchase', methods: ['POST'])]
    public function userCancelPurchase(Order $order, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        // Check if current user is the buyer of this order
        $currentUser = $this->getUser();
        $isBuyer = $order->getBuyer() && $order->getBuyer()->getId() === $currentUser->getId();

        if (!$isBuyer) {
            $this->addFlash('error', 'You can only cancel your own purchases!');
            return $this->redirectToRoute('my_listings');
        }

        // Only allow cancellation if order is pending
        if ($order->getStatus() !== 'pending') {
            $this->addFlash('error', 'Only pending purchases can be cancelled!');
            return $this->redirectToRoute('my_listings');
        }

        $order->setStatus('cancelled');
        
        // Make listing available again when cancelled (if listing still exists)
        if ($order->getListing()) {
            $order->getListing()->setIsForSale(true);
        }
        
        $entityManager->flush();

        $logger->log(
            $currentUser,
            'ORDER_UPDATE',
            'User cancelled purchase: ' . $order->getCollectibleName() . ' (ID: ' . $order->getId() . ')'
        );

        $this->addFlash('warning', 'Purchase cancelled. Listing is available again.');
        return $this->redirectToRoute('my_listings');
    }

    #[Route('/purchase/{id}/complete', name: 'order_user_complete_purchase', methods: ['POST'])]
public function completePurchase(
    Order $order, 
    Request $request, 
    EntityManagerInterface $entityManager, 
    ActivityLogger $logger
): Response
{
    $currentUser = $this->getUser();
    
    // Check if current user is the buyer
    if ($order->getBuyer() !== $currentUser) {
        $this->addFlash('error', 'You can only complete your own purchases!');
        return $this->redirectToRoute('my_listings');
    }
    
    // Check if order is in confirmed status
    if ($order->getStatus() !== 'confirmed') {
        $this->addFlash('error', 'Only confirmed orders can be marked as completed!');
        return $this->redirectToRoute('my_listings');
    }
    
    // Validate CSRF token
    if (!$this->isCsrfTokenValid('complete_purchase' . $order->getId(), $request->request->get('_token'))) {
        $this->addFlash('error', 'Invalid security token.');
        return $this->redirectToRoute('my_listings');
    }
    
    // Get the listing and collectible
    $listing = $order->getListing();
    
    if (!$listing) {
        $this->addFlash('error', 'Listing not found for this order!');
        return $this->redirectToRoute('my_listings');
    }
    
    $collectible = $listing->getCollectible();
    
    if (!$collectible) {
        $this->addFlash('error', 'Collectible not found for this listing!');
        return $this->redirectToRoute('my_listings');
    }
    
    try {
        // 1. Transfer collectible ownership to buyer (current user)
        $collectible->setUser($currentUser);
        
        // 2. Set listing to null to break the foreign key relationship
        $order->setListing(null);
        
        // 3. Flush to persist the null relationship
        $entityManager->flush();
        
        // 4. Delete the listing (foreign key constraint is broken)
        $entityManager->remove($listing);
        
        // 5. Update order status and completion time
        $order->setStatus('completed');
        $order->setCompletedAt(new \DateTimeImmutable('now', new \DateTimeZone('Asia/Manila')));
        
        $entityManager->flush();
        
        // Log the activity
        $logger->log(
            $currentUser,
            'ORDER_UPDATE',
            'Buyer marked purchase as completed: ' . $order->getCollectibleName() . 
            ' (Order ID: ' . $order->getId() . ') - ' .
            'Collectible transferred from ' . $order->getSeller()->getUsername() . 
            ' to ' . $currentUser->getUsername() . 
            ' | Price: ₱' . number_format($order->getPurchasePrice(), 2)
        );
        
        $this->addFlash('success', 'Purchase marked as completed! Collectible has been transferred to your collection.');
        
    } catch (\Exception $e) {
        $this->addFlash('error', 'Failed to complete purchase. Please try again.');
        // Optional: log the error
        // $logger->log($currentUser, 'ERROR', 'Failed to complete purchase: ' . $e->getMessage());
    }
    
    return $this->redirectToRoute('my_listings');
}
}