<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/order')]
final class OrderController extends AbstractController
{
    #[Route(name: 'app_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        if (!$this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'You need staff or admin privileges to view orders!');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('order/index.html.twig', [
            'orders' => $orderRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        ActivityLogger $logger
    ): Response
    {
        if (!$this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'You need staff or admin privileges to create orders!');
            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // CHECK: Buyer cannot buy their own listing
            if ($order->getBuyer()->getId() === $order->getListing()->getUser()->getId()) {
                $this->addFlash('error', 'Buyer cannot purchase their own listing!');
                return $this->redirectToRoute('app_order_new', [], Response::HTTP_SEE_OTHER);
            }

            // Set createdBy (current admin/staff)
            $order->setCreatedBy($this->getUser());

            // Auto-fill from listing
            if ($order->getListing()) {
                $listing = $order->getListing();
                $order->setCollectibleName($listing->getCollectible()->getName());
                $order->setPurchasePrice($listing->getPrice());
                $order->setSeller($listing->getUser());
            }

            // Check if status is "completed" on creation
            if ($order->getStatus() === 'completed' && $order->getListing()) {
                $listing = $order->getListing();
                $collectible = $listing->getCollectible();
                
                if ($collectible) {
                    // Transfer ownership to buyer
                    $collectible->setUser($order->getBuyer());
                    
                    // Remove listing reference from order (set to null)
                    $order->setListing(null);
                    
                    // Delete listing
                    $entityManager->remove($listing);
                    
                    $logger->log($this->getUser(), 'TRANSFER_COLLECTIBLE',
                        'Transferred collectible on order creation: ' . $collectible->getName()
                    );
                }
            } else {
                // Only hide listing if not completed
                $order->getListing()->setIsForSale(false);
            }

            $entityManager->persist($order);
            $entityManager->flush();

            // Log the activity
            $currentUser = $this->getUser();
            $logger->log($currentUser, 'CREATE_ORDER',
                'Created order for collectible: ' . $order->getCollectibleName() . ' (ID: ' . $order->getId() . ')'
            );

            $this->addFlash('success', 'Order created successfully!' . 
                ($order->getStatus() === 'completed' ? ' Collectible transferred to buyer and listing removed.' : ''));
            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/new.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        if (!$this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'You need staff or admin privileges to view orders!');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Order $order, 
        EntityManagerInterface $entityManager,
        ActivityLogger $logger
    ): Response
    {
        if (!$this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'You need staff or admin privileges to edit orders!');
            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        // Staff can only edit their own created orders (unless admin)
        if ($this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            if ($order->getCreatedBy() !== $this->getUser()) {
                $this->addFlash('error', 'You can only edit your own created orders!');
                return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        // Store original status to detect changes
        $originalStatus = $order->getStatus();
        
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // CHECK: Buyer cannot buy their own listing (even on edit)
            if ($order->getBuyer()->getId() === $order->getListing()->getUser()->getId()) {
                $this->addFlash('error', 'Buyer cannot purchase their own listing!');
                return $this->redirectToRoute('app_order_edit', ['id' => $order->getId()], Response::HTTP_SEE_OTHER);
            }

            // Check if status changed to "completed"
            $newStatus = $order->getStatus();
            $isNowCompleted = ($originalStatus !== 'completed' && $newStatus === 'completed');
            
            if ($isNowCompleted && $order->getListing()) {
                $listing = $order->getListing();
                $collectible = $listing->getCollectible();
                
                if ($collectible) {
                    // 1. Transfer collectible ownership to buyer
                    $collectible->setUser($order->getBuyer());
                    
                    // 2. Remove the listing reference from the order
                    $order->setListing(null);
                    
                    // 3. Delete the listing
                    $entityManager->remove($listing);
                    
                    $logger->log($this->getUser(), 'TRANSFER_COLLECTIBLE',
                        'Transferred collectible: ' . $collectible->getName() . 
                        ' from seller: ' . $order->getSeller()->getUsername() .
                        ' to buyer: ' . $order->getBuyer()->getUsername()
                    );
                }
            }
            
            $entityManager->flush();

            // Log the activity
            $currentUser = $this->getUser();
            $logger->log($currentUser, 'UPDATE_ORDER',
                'Updated order: ' . $order->getCollectibleName() . ' (ID: ' . $order->getId() . ')' .
                ($isNowCompleted ? ' - Status changed to COMPLETED' : '')
            );

            $this->addFlash('success', 'Order updated successfully!' . 
                ($isNowCompleted ? ' Collectible transferred to buyer and listing removed.' : ''));
            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_order_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        Order $order, 
        EntityManagerInterface $entityManager,
        ActivityLogger $logger
    ): Response
    {
        if (!$this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'You need staff or admin privileges to delete orders!');
            $response = $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
            $response->headers->set('Turbo-Location', 'false');
            return $response;
        }

        // Staff can only delete their own created orders (unless admin)
        if ($this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            if ($order->getCreatedBy() !== $this->getUser()) {
                $this->addFlash('error', 'You can only delete your own created orders!');
                $response = $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
                $response->headers->set('Turbo-Location', 'false');
                return $response;
            }
        }

        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->getPayload()->getString('_token'))) {
            // Make listing available again when order is deleted (if listing exists and order wasn't completed)
            if ($order->getListing() && $order->getStatus() !== 'completed') {
                $order->getListing()->setIsForSale(true);
            }

            // Log the activity
            $currentUser = $this->getUser();
            $logger->log($currentUser, 'DELETE_ORDER',
                'Deleted order: ' . $order->getCollectibleName() . ' (ID: ' . $order->getId() . ')'
            );

            $entityManager->remove($order);
            $entityManager->flush();
            
            $this->addFlash('success', 'Order deleted successfully!');
        }

        $response = $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        $response->headers->set('Turbo-Location', 'false');
        return $response;
    }

    // QUICK ACTION ROUTES:

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
        $logger->log($currentUser, 'CONFIRM_ORDER',
            'Confirmed order: ' . $order->getCollectibleName() . ' (ID: ' . $order->getId() . ')'
        );

        $this->addFlash('success', 'Order confirmed successfully.');
        return $this->redirectToRoute('app_order_index');
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
    
    // 2. FIRST set listing to null (break the foreign key relationship)
    $order->setListing(null);
    
    // 3. Flush to persist the null relationship
    $entityManager->flush();
    
    // 4. NOW delete the listing (foreign key constraint is broken)
    $entityManager->remove($listing);
    
    // 5. Update order status
    $order->setStatus('completed');
    
    $entityManager->flush();

    // Log the activity
    $currentUser = $this->getUser();
    $logger->log($currentUser, 'COMPLETE_ORDER',
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
        $logger->log($currentUser, 'CANCEL_ORDER',
            'Cancelled order: ' . $order->getCollectibleName() . ' (ID: ' . $order->getId() . ')'
        );

        $this->addFlash('warning', 'Order cancelled.' . 
            ($order->getListing() ? ' Listing is available for sale again.' : ''));
        return $this->redirectToRoute('app_order_index');
    }
}