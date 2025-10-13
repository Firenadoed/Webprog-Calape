<?php

namespace App\Controller;

use App\Entity\Collectible;
use App\Entity\Listing;
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
    public function add(Request $request, EntityManagerInterface $em, CsrfTokenManagerInterface $csrf): JsonResponse
    {
        $collectibleId = $request->request->get('collectible_id');
        $grade = $request->request->get('grade'); // ✅ Get grade valu
        if (!$collectibleId) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'No collectible selected!'
            ]);
        }

        $collectible = $em->getRepository(Collectible::class)->find($collectibleId);

        if (!$collectible) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Collectible not found!'
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
                'deleteToken' => $deleteToken, // ✅ include token here
            ]);
        }
        $listing = new Listing();
        $listing->setGrade($grade); // ✅ Save it to DB
        $listing->setCollectible($collectible);
        $listing->setPrice($collectible->getPrice() ?? 0.0);
        $listing->setIsForSale(true);
        $listing->setIsShopItem(false);
        $listing->setCreatedAt(new \DateTime());

        $em->persist($listing);
        $em->flush();

        // ✅ generate CSRF delete token for this new listing
        $deleteToken = $csrf->getToken('delete' . $listing->getId())->getValue();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Collectible "' . $collectible->getName() . '" is now listed for sale!',
            'listingId' => $listing->getId(),
            'deleteToken' => $deleteToken, // ✅ send it to frontend
        ]);
    }

    #[Route('/delete/{id}', name: 'app_listing_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, EntityManagerInterface $em): JsonResponse
    {
        $listing = $em->getRepository(Listing::class)->find($id);

        if (!$listing) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Listing not found.'
            ]);
        }

        if (!$this->isCsrfTokenValid('delete' . $listing->getId(), $request->request->get('_token'))) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid CSRF token.'
            ]);
        }

        $em->remove($listing);
        $em->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Listing removed successfully.'
        ]);
    }
}
