<?php

namespace App\Controller;

use App\Entity\Collectible;
use App\Entity\Listing;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/listing')]
final class ListingAddController extends AbstractController
{
    #[Route('/add', name: 'listing_add', methods: ['POST'])]
    public function add(
        Request $request, 
        EntityManagerInterface $em, 
        CsrfTokenManagerInterface $csrf,
        ActivityLogger $logger
    ): JsonResponse
    {
        $collectibleId = $request->request->get('collectible_id');
        $grade = $request->request->get('grade');
        $price = $request->request->get('price');
        
        if (!$collectibleId) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'No collectible selected!'
            ]);
        }

        if (!$price || !is_numeric($price) || floatval($price) <= 0) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Please enter a valid price (minimum ₱1.00)!'
            ]);
        }

        $collectible = $em->getRepository(Collectible::class)->find($collectibleId);

        if (!$collectible) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Collectible not found!'
            ]);
        }

        $currentUser = $this->getUser();
        if ($collectible->getUser()->getId() !== $currentUser->getId()) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'You do not own this collectible!'
            ]);
        }

        $existingListing = $em->getRepository(Listing::class)->findOneBy([
            'collectible' => $collectible,
            'is_for_sale' => true,
        ]);

        if ($existingListing) {
            $deleteToken = $csrf->getToken('delete' . $existingListing->getId())->getValue();

            return new JsonResponse([
                'status' => 'warning',
                'message' => 'This collectible is already listed for sale!',
                'listingId' => $existingListing->getId(),
                'deleteToken' => $deleteToken,
            ]);
        }

        $listing = new Listing();
        $listing->setGrade($grade);
        $listing->setCollectible($collectible);
        $listing->setPrice(floatval($price));
        $listing->setIsForSale(true);
        $listing->setUser($currentUser);

        $em->persist($listing);
        $em->flush();

        // Log the listing creation using ActivityLogger service
        $logger->log(
            $currentUser,
            'CREATE_LISTING',
            'Listing created: ' . $collectible->getName() . ' (Collectible ID: ' . $collectible->getId() . ') - Price: ₱' . $price . ' - Grade: ' . ($grade ?: 'N/A')
        );

        $deleteToken = $csrf->getToken('delete' . $listing->getId())->getValue();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Collectible "' . $collectible->getName() . '" is now listed for sale!',
            'listingId' => $listing->getId(),
            'deleteToken' => $deleteToken,
        ]);
    }

    #[Route('/delete/{id}', name: 'listing_remove', methods: ['POST'])]
    public function delete(
        Request $request, 
        int $id, 
        EntityManagerInterface $em,
        ActivityLogger $logger
    ): JsonResponse
    {
        $listing = $em->getRepository(Listing::class)->find($id);

        if (!$listing) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Listing not found.'
            ]);
        }

        $currentUser = $this->getUser();
        if ($listing->getUser()->getId() !== $currentUser->getId()) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'You do not own this listing!'
            ]);
        }

        if (!$this->isCsrfTokenValid('delete' . $listing->getId(), $request->request->get('_token'))) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid CSRF token.'
            ]);
        }

        // Store info before deletion
        $collectibleName = $listing->getCollectible()->getName();
        $collectibleId = $listing->getCollectible()->getId();
        $price = $listing->getPrice();

        // Log before deletion
        $logger->log(
            $currentUser,
            'DELETE_LISTING',
            'Listing deleted: ' . $collectibleName . ' (Collectible ID: ' . $collectibleId . ') - Price: ₱' . $price
        );

        $em->remove($listing);
        $em->flush();
        
        return new JsonResponse([
            'status' => 'success',
            'message' => 'Listing removed successfully.'
        ]);
    }
}