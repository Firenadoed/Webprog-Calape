<?php

namespace App\Controller;

use App\Entity\Collectible;
use App\Entity\Listing;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/listing')]
final class ListingAddController extends AbstractController
{
    public function __construct(private CsrfTokenManagerInterface $csrfTokenManager) {}

    #[Route('/add', name: 'listing_add', methods: ['POST'])]
    public function add(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $collectibleId = $request->request->get('collectible_id');
        $token = $request->request->get('_token');

        if (!$collectibleId) {
            return new JsonResponse(['success' => false, 'message' => 'No collectible selected!']);
        }

        $csrfId = 'add_listing_' . $collectibleId;
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($csrfId, $token))) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token.']);
        }

        $collectible = $em->getRepository(Collectible::class)->find($collectibleId);
        if (!$collectible) {
            return new JsonResponse(['success' => false, 'message' => 'Collectible not found!']);
        }

        $existingListing = $em->getRepository(Listing::class)->findOneBy([
            'collectible' => $collectible,
            'is_for_sale' => true,
        ]);

        if ($existingListing) {
            return new JsonResponse([
                'success' => false,
                'message' => sprintf('"%s" is already listed for sale!', $collectible->getName())
            ]);
        }

        $listing = new Listing();
        $listing->setCollectible($collectible);
        $listing->setPrice($collectible->getPrice() ?? 0.0);
        $listing->setIsForSale(true);
        $listing->setIsShopItem(false);
        $listing->setCreatedAt(new \DateTime());

        $em->persist($listing);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => sprintf('"%s" is now listed for sale!', $collectible->getName()),
            'action' => 'added',
            'listingId' => $listing->getId(),
            'deleteUrl' => $this->generateUrl('app_listing_delete', ['id' => $listing->getId()]),
            'deleteToken' => $this->csrfTokenManager->getToken('delete' . $listing->getId())->getValue()
        ]);
    }

    #[Route('/delete/{id<\d+>}', name: 'app_listing_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, EntityManagerInterface $em): JsonResponse
    {
        $listing = $em->getRepository(Listing::class)->find($id);

        if (!$listing) {
            return new JsonResponse(['success' => false, 'message' => 'Listing not found.']);
        }

        $token = $request->request->get('_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('delete' . $listing->getId(), $token))) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token.']);
        }

        $collectible = $listing->getCollectible();

        $em->remove($listing);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => sprintf('"%s" has been removed from sale!', $collectible->getName()),
            'action' => 'deleted',
            'addUrl' => $this->generateUrl('listing_add'),
            'addToken' => $this->csrfTokenManager->getToken('add_listing_' . $collectible->getId())->getValue()
        ]);
    }
}
