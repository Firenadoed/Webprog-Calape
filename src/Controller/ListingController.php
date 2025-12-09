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
            $listing->setCreatedBy($this->getUser());
            
            $entityManager->persist($listing);
            $entityManager->flush();

            $currentUser = $this->getUser();
            $logger->log($currentUser, 'CREATE_LISTING',
                'Created listing: ' . $listing->getTitle() . ' (ID: ' . $listing->getId() . ')'
            );

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
                'Updated listing: ' . $listing->getTitle() . ' (ID: ' . $listing->getId() . ')'
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
        if ($this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            if ($listing->getCreatedBy() !== $this->getUser()) {
                $this->addFlash('error', 'You can only delete your own listings!');
                $response = $this->redirectToRoute('app_listing_index', [], Response::HTTP_SEE_OTHER);
                $response->headers->set('Turbo-Location', 'false');
                return $response;
            }
        }

        if ($listing->getCreatedBy() !== $this->getUser()) {
            $this->addFlash('error', 'You can only delete your own listings!');
            $response = $this->redirectToRoute('app_listing_index', [], Response::HTTP_SEE_OTHER);
            $response->headers->set('Turbo-Location', 'false');
            return $response;
        }

        if ($this->isCsrfTokenValid('delete'.$listing->getId(), $request->getPayload()->getString('_token'))) {
            $currentUser = $this->getUser();
            $logger->log($currentUser, 'DELETE_LISTING',
                'Deleted listing: ' . $listing->getTitle() . ' (ID: ' . $listing->getId() . ')'
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