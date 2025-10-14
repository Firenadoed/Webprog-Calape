<?php

namespace App\Controller;

use App\Entity\Listing;
use App\Entity\Collectible;
use App\Form\ListingType;
use App\Repository\ListingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;

#[Route('/listing')]
final class ListingController extends AbstractController
{
    #[Route(name: 'app_listing_index', methods: ['GET'])]
public function index(Request $request, ListingRepository $listingRepository): Response
{
    $search = $request->query->get('search'); // Get search term
    $filter = $request->query->get('filter'); // Get filter term (optional)

    $qb = $listingRepository->createQueryBuilder('l')
        ->leftJoin('l.user', 'u')
        ->leftJoin('l.collectible', 'c')
        ->addSelect('u', 'c');

    // Apply search
    if ($search) {
        $qb->andWhere('l.grade LIKE :search OR u.username LIKE :search OR c.name LIKE :search')
           ->setParameter('search', '%'.$search.'%');
    }

    // Apply filter
    if ($filter === 'for_sale') {
        $qb->andWhere('l.isForSale = :forSale')
           ->setParameter('forSale', true);
    }

    $listings = $qb->getQuery()->getResult();

    return $this->render('listing/index.html.twig', [
        'listings' => $listings,
    ]);
}


    #[Route('/new', name: 'app_listing_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $listing = new Listing();
        $form = $this->createForm(ListingType::class, $listing);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($listing);
            $entityManager->flush();

            return $this->redirectToRoute('app_listing_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('listing/new.html.twig', [
            'listing' => $listing,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_listing_show', methods: ['GET'])]
    public function show(Listing $listing): Response
    {
        return $this->render('listing/show.html.twig', [
            'listing' => $listing,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_listing_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Listing $listing, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ListingType::class, $listing);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_listing_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('listing/edit.html.twig', [
            'listing' => $listing,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_listing_deleted', methods: ['POST'])]
    public function delete(Request $request, Listing $listing, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$listing->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($listing);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_listing_index', [], Response::HTTP_SEE_OTHER);
    }



}
