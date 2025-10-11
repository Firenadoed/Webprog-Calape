<?php

namespace App\Controller;

use App\Entity\Collectible;
use App\Entity\Category; // <--- This 'use' statement is correct
use App\Form\CollectibleType;
use App\Repository\CollectibleRepository;
use App\Repository\CategoryRepository; // <--- This 'use' statement is correct
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request; // <--- MAKE SURE THIS IS PRESENT
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/collectible')]
final class CollectibleController extends AbstractController
{
    #[Route(name: 'app_collectible_index', methods: ['GET'])]
    public function index(
        CollectibleRepository $collectibleRepository,
        CategoryRepository $categoryRepository, // <--- INJECT CategoryRepository here
        Request $request // <--- INJECT Request here
    ): Response {
        // Get search and category filter parameters from the request
        $searchTerm = $request->query->get('search');
        $categoryId = $request->query->get('category');

        // Fetch all categories for the filter dropdown
        $categories = $categoryRepository->findAll();

        // Use the custom findByFilters method from CollectibleRepository
        $collectibles = $collectibleRepository->findByFilters($searchTerm, $categoryId);

        return $this->render('collectible/index.html.twig', [
            'collectibles' => $collectibles,
            'categories' => $categories, // <--- PASS 'categories' to Twig
        ]);
    }

    #[Route('/new', name: 'app_collectible_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $collectible = new Collectible();
        $form = $this->createForm(CollectibleType::class, $collectible);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('collectibles_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Image could not be uploaded.');
                }

                $collectible->setImage($newFilename);
            }

            $entityManager->persist($collectible);
            $entityManager->flush();

            return $this->redirectToRoute('app_collectible_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('collectible/new.html.twig', [
            'collectible' => $collectible,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_collectible_show', methods: ['GET'])]
    public function show(Collectible $collectible): Response
    {
        return $this->render('collectible/show.html.twig', [
            'collectible' => $collectible,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_collectible_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Collectible $collectible,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(CollectibleType::class, $collectible);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('collectibles_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Image could not be uploaded.');
                }

                $collectible->setImage($newFilename);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_collectible_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('collectible/edit.html.twig', [
            'collectible' => $collectible,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_collectible_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Collectible $collectible,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$collectible->getId(), $request->request->get('_token'))) {
            $entityManager->remove($collectible);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_collectible_index', [], Response::HTTP_SEE_OTHER);
    }
}