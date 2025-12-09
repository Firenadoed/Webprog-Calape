<?php

namespace App\Controller;

use App\Entity\Collectible;
use App\Form\CollectibleType;
use App\Repository\CollectibleRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/collectible')]
final class CollectibleController extends AbstractController
{
    #[Route(name: 'app_collectible_index', methods: ['GET'])]
    public function index(CollectibleRepository $collectibleRepository): Response
    {
        $collectibles = $collectibleRepository->findAll();

        return $this->render('collectible/index.html.twig', [
            'collectibles' => $collectibles,
        ]);
    }

    #[Route('/new', name: 'app_collectible_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        ActivityLogger $logger
    ): Response
    {
        $collectible = new Collectible();
        $form = $this->createForm(CollectibleType::class, $collectible);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $collectibleImageFile = $form->get('image')->getData();
            if ($collectibleImageFile) {
                $originalFilename = pathinfo($collectibleImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$collectibleImageFile->guessExtension();

                try {
                    $collectibleImageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/collectibles',
                        $newFilename
                    );
                    $collectible->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                    $collectible->setImage('default_collectible.jpg');
                }
            } else {
                $collectible->setImage('default_collectible.jpg');
            }

            $collectible->setCreatedBy($this->getUser());

            $entityManager->persist($collectible);
            $entityManager->flush();

            $currentUser = $this->getUser();
            $logger->log($currentUser, 'CREATE_COLLECTIBLE',
                'Created collectible: ' . $collectible->getName() . ' (ID: ' . $collectible->getId() . ')'
            );

            $this->addFlash('success', 'Collectible created successfully!');
            return $this->redirectToRoute('app_collectible_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('collectible/new.html.twig', [
            'collectible' => $collectible,
            'form' => $form->createView(),
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
        SluggerInterface $slugger,
        ActivityLogger $logger
    ): Response
    {
        if ($this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            if ($collectible->getCreatedBy() !== $this->getUser()) {
                $this->addFlash('error', 'You can only edit your own collectibles!');
                return $this->redirectToRoute('app_collectible_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        $form = $this->createForm(CollectibleType::class, $collectible);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $collectibleImageFile = $form->get('image')->getData();
            if ($collectibleImageFile) {
                $originalFilename = pathinfo($collectibleImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$collectibleImageFile->guessExtension();

                try {
                    $collectibleImageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/collectibles',
                        $newFilename
                    );
                    
                    $oldImage = $collectible->getImage();
                    if ($oldImage && $oldImage !== 'default_collectible.jpg') {
                        $oldImagePath = $this->getParameter('kernel.project_dir').'/public/uploads/collectibles/'.$oldImage;
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    
                    $collectible->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                }
            }

            $entityManager->flush();

            $currentUser = $this->getUser();
            $logger->log($currentUser, 'UPDATE_COLLECTIBLE',
                'Updated collectible: ' . $collectible->getName() . ' (ID: ' . $collectible->getId() . ')'
            );

            $this->addFlash('success', 'Collectible updated successfully!');
            return $this->redirectToRoute('app_collectible_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('collectible/edit.html.twig', [
            'collectible' => $collectible,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_collectible_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        Collectible $collectible, 
        EntityManagerInterface $entityManager,
        ActivityLogger $logger
    ): Response
    {
        if ($this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            if ($collectible->getCreatedBy() !== $this->getUser()) {
                $this->addFlash('error', 'You can only delete your own collectibles!');
                return $this->redirectToRoute('app_collectible_index', [], Response::HTTP_SEE_OTHER)
                ->headers->set('Turbo-Location', 'false');
            }
        }

        if ($collectible->getCreatedBy() !== $this->getUser()) {
            $this->addFlash('error', 'You can only delete your own collectibles!');
            return $this->redirectToRoute('app_collectible_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($this->isCsrfTokenValid('delete'.$collectible->getId(), $request->getPayload()->getString('_token'))) {
            $currentUser = $this->getUser();
            $logger->log($currentUser, 'DELETE_COLLECTIBLE',
                'Deleted collectible: ' . $collectible->getName() . ' (ID: ' . $collectible->getId() . ')'
            );
            
            $collectibleImage = $collectible->getImage();
            if ($collectibleImage && $collectibleImage !== 'default_collectible.jpg') {
                $imagePath = $this->getParameter('kernel.project_dir').'/public/uploads/collectibles/'.$collectibleImage;
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            $entityManager->remove($collectible);
            $entityManager->flush();
            
            $this->addFlash('success', 'Collectible deleted successfully!');
        }

        return $this->redirectToRoute('app_collectible_index', [], Response::HTTP_SEE_OTHER);
    }
}