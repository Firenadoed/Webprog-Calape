<?php

namespace App\Controller;

use App\Entity\Listing;
use App\Form\ListingType;
use App\Repository\ListingRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/listing')]
final class ListingController extends AbstractController
{
    #[Route(name: 'app_listing_index', methods: ['GET'])]
    public function index(ListingRepository $listingRepository): Response
    {
        $listings = $listingRepository->findAll();

        return $this->render('listing/index.html.twig', [
            'listings' => $listings,
        ]);
    }

    #[Route('/new', name: 'app_listing_new', methods: ['GET', 'POST'])]
   public function new(
    Request $request, 
    EntityManagerInterface $entityManager,
    ActivityLogger $logger
): Response
{
    if (!$this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
        $this->addFlash('error', 'You need staff or admin privileges to create listings!');
        return $this->redirectToRoute('app_listing_index', [], Response::HTTP_SEE_OTHER);
    }

    $listing = new Listing();
    $form = $this->createForm(ListingType::class, $listing);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // CRITICAL: Check if collectible is set BEFORE trying to save
        if (!$listing->getCollectible()) {
            return $this->render('listing/new.html.twig', [
                'listing' => $listing,
                'form' => $form->createView(),
            ]);
        }
        
        $listing->setCreatedBy($this->getUser());
        
        $entityManager->persist($listing);
        $entityManager->flush();

        // Now it's safe to call getName() since we validated collectible exists
        $currentUser = $this->getUser();
        $logger->log($currentUser, 'CREATE_LISTING',
            'Created listing for: ' . $listing->getCollectible()->getName() . ' (ID: ' . $listing->getId() . ')'
        );

        $this->addFlash('success', 'Listing created successfully!');
        return $this->redirectToRoute('app_listing_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('listing/new.html.twig', [
        'listing' => $listing,
        'form' => $form->createView(),
    ]);
}

    #[Route('/{id}/edit', name: 'app_listing_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Listing $listing, 
        EntityManagerInterface $entityManager,
        ActivityLogger $logger
    ): Response
    {
        if ($this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            if ($listing->getCreatedBy() !== $this->getUser()) {
                $this->addFlash('error', 'You can only edit your own listings!');
                return $this->redirectToRoute('app_listing_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        $form = $this->createForm(ListingType::class, $listing);
        $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $currentUser = $this->getUser();
            $logger->log($currentUser, 'UPDATE_LISTING',
                'Updated listing for: ' . $listing->getCollectible()->getName() . ' (ID: ' . $listing->getId() . ')'
            );

            return $this->redirectToRoute('app_listing_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('listing/edit.html.twig', [
            'listing' => $listing,
            'form' => $form,
        ]);
    }

  #[Route('/{id}', name: 'app_listing_delete', methods: ['POST'])]
public function delete(
    Request $request, 
    Listing $listing, 
    EntityManagerInterface $entityManager,
    ActivityLogger $logger
): Response
{
    // Check if user has permission to delete
    $canDelete = false;
    
    // Admin can delete any listing
    if ($this->isGranted('ROLE_ADMIN')) {
        $canDelete = true;
    }
    // Staff can only delete their own listings
    elseif ($this->isGranted('ROLE_STAFF') && $listing->getCreatedBy() === $this->getUser()) {
        $canDelete = true;
    }
    
    if (!$canDelete) {
        $this->addFlash('error', 'You do not have permission to delete this listing!');
        $response = $this->redirectToRoute('app_listing_index', [], Response::HTTP_SEE_OTHER);
        $response->headers->set('Turbo-Location', 'false');
        return $response;
    }

if ($this->isCsrfTokenValid('delete'.$listing->getId(), $request->getPayload()->getString('_token'))) {
    $currentUser = $this->getUser();
    
    $logger->log($currentUser, 'DELETE_LISTING',
        'Deleted listing for: ' . $listing->getCollectible()->getName() . ' (ID: ' . $listing->getId() . ')'
    );
    
    $entityManager->remove($listing);
    $entityManager->flush();
    
    $this->addFlash('success', 'Listing deleted successfully!');
}
    $response = $this->redirectToRoute('app_listing_index', [], Response::HTTP_SEE_OTHER);
    $response->headers->set('Turbo-Location', 'false');
    return $response;
}
}