<?php

namespace App\Controller;

use App\Entity\Collectible;
use App\Entity\Listing;
use App\Entity\ActivityLog;
use App\Entity\User;
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
           $deleteToken = $csrf->getToken('delete' . $listing->getId())->getValue();

            return new JsonResponse([
                'status' => 'warning',
                'message' => 'This collectible is already listed for sale!',
                'listingId' => $existingListing->getId(),
                'deleteToken' => $deleteToken, // ✅ include token here
            ]);
        }
        $userRepository = $em->getRepository(\App\Entity\User::class);
        $hardcodedUser = $userRepository->find(1);


        $listing = new Listing();
        $listing->setGrade($grade); // ✅ Save it to DB
        $listing->setCollectible($collectible);
        $listing->setPrice($collectible->getPrice() ?? 0.0);
        $listing->setIsForSale(true);
        $listing->setIsShopItem(false);
        $listing->setUser($hardcodedUser); // or $this->getUser() if live
        $listing->setCreatedAt(new \DateTime());

        $em->persist($listing);
        $em->flush();

        $activity = new ActivityLog();
        $activity->setUser($hardcodedUser); // or $this->getUser() if live
        $activity->setEntityType('Listing');
        $activity->setEntityId($listing->getId());
        $activity->setAction('Added');
        $activity->setDetails('Added Listing: ' . $collectible->getName());
        $activity->setCreatedAt(new \DateTimeImmutable());

      
        $em->persist($activity);
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

        $userRepository = $em->getRepository(\App\Entity\User::class);
        $hardcodedUser = $userRepository->find(1);

        $activity = new ActivityLog();
        $activity->setUser($hardcodedUser); // or $this->getUser() if live
        $activity->setEntityType('Listing');
        $activity->setEntityId($listing->getId());
        $activity->setAction('Deleted');
        $activity->setDetails('Deleted Listing: ' . $listing->getCollectible()->getName());
        $activity->setCreatedAt(new \DateTimeImmutable());

        $em->remove($listing);
        $em->persist($activity);
        $em->flush();
        return new JsonResponse([
            'status' => 'success',
            'message' => 'Listing removed successfully.'
        ]);
    }

    
}
