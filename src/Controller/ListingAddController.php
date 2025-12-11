<?php

namespace App\Controller;

use App\Entity\Collectible;
use App\Entity\Listing;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/my-collection/listing')]
final class ListingAddController extends AbstractController
{
    #[Route('/add', name: 'listing_add', methods: ['POST'])]
    public function add(
        Request $request, 
        EntityManagerInterface $em,
        ActivityLogger $logger
    ): Response
    {
        $collectibleId = $request->request->get('collectible_id');
        $grade = $request->request->get('grade');
        $price = $request->request->get('price');
        
        if (!$collectibleId) {
            $this->addFlash('error', 'No collectible selected!');
            return $this->redirectToRoute('collection');
        }

        if (!$price || !is_numeric($price) || floatval($price) <= 0) {
            $this->addFlash('error', 'Please enter a valid price (minimum ₱1.00)!');
            return $this->redirectToRoute('collection');
        }

        $collectible = $em->getRepository(Collectible::class)->find($collectibleId);

        if (!$collectible) {
            $this->addFlash('error', 'Collectible not found!');
            return $this->redirectToRoute('collection');
        }

        $currentUser = $this->getUser();
        if ($collectible->getUser()->getId() !== $currentUser->getId()) {
            $this->addFlash('error', 'You do not own this collectible!');
            return $this->redirectToRoute('collection');
        }

        $existingListing = $em->getRepository(Listing::class)->findOneBy([
            'collectible' => $collectible,
            'is_for_sale' => true,
        ]);

        if ($existingListing) {
            $this->addFlash('warning', 'This collectible is already listed for sale!');
            return $this->redirectToRoute('collection');
        }

        $listing = new Listing();
        $listing->setGrade($grade);
        $listing->setCollectible($collectible);
        $listing->setPrice(floatval($price));
        $listing->setIsForSale(true);
        $listing->setUser($currentUser);

        $em->persist($listing);
        $em->flush();

        // Log listing creation
        $logger->log(
            $currentUser,
            'CREATE_LISTING',
            'Created listing for collectible: ' . $collectible->getName() . 
            ' (Collectible ID: ' . $collectible->getId() . ', Listing ID: ' . $listing->getId() . ') - ' .
            'Price: ₱' . number_format($price, 2) . ' - Grade: ' . ($grade ?: 'Not specified')
        );

        $this->addFlash('success', 'Collectible "' . $collectible->getName() . '" is now listed for sale!');
        return $this->redirectToRoute('collection');
    }

    #[Route('/delete/{id}', name: 'listing_remove', methods: ['POST'])]
    public function delete(
        Request $request, 
        int $id, 
        EntityManagerInterface $em,
        ActivityLogger $logger
    ): Response
    {
        $listing = $em->getRepository(Listing::class)->find($id);

        if (!$listing) {
            $this->addFlash('error', 'Listing not found.');
            return $this->redirectToRoute('collection');
        }

        $currentUser = $this->getUser();
        if ($listing->getUser()->getId() !== $currentUser->getId()) {
            $this->addFlash('error', 'You do not own this listing!');
            return $this->redirectToRoute('collection');
        }

        if (!$this->isCsrfTokenValid('delete' . $listing->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('collection');
        }

        // Get collectible info for logging BEFORE deletion
        $collectibleName = $listing->getCollectible() ? $listing->getCollectible()->getName() : 'Unknown Collectible';
        $collectibleId = $listing->getCollectible() ? $listing->getCollectible()->getId() : null;
        $listingPrice = $listing->getPrice();
        $listingGrade = $listing->getGrade();

        // Log listing deletion BEFORE removal
        $logger->log(
            $currentUser,
            'DELETE_LISTING',
            'Deleted listing for collectible: ' . $collectibleName . 
            ' (Collectible ID: ' . ($collectibleId ?: 'N/A') . ', Listing ID: ' . $listing->getId() . ') - ' .
            'Price: ₱' . number_format($listingPrice, 2) . ' - Grade: ' . ($listingGrade ?: 'Not specified')
        );

        $em->remove($listing);
        $em->flush();
        
        $this->addFlash('success', 'Listing removed successfully.');
        return $this->redirectToRoute('collection');
    }
}