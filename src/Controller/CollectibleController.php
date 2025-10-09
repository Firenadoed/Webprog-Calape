<?php

namespace App\Controller;

use App\Entity\Collectible;
use App\Form\CollectibleType;
use App\Repository\CollectibleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/collectible')]
final class CollectibleController extends AbstractController
{
    #[Route(name: 'app_collectible_index', methods: ['GET'])]
    public function index(CollectibleRepository $collectibleRepository): Response
    {
        return $this->render('collectible/index.html.twig', [
            'collectibles' => $collectibleRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_collectible_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $collectible = new Collectible();
        $form = $this->createForm(CollectibleType::class, $collectible);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
    public function edit(Request $request, Collectible $collectible, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CollectibleType::class, $collectible);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_collectible_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('collectible/edit.html.twig', [
            'collectible' => $collectible,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_collectible_delete', methods: ['POST'])]
    public function delete(Request $request, Collectible $collectible, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$collectible->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($collectible);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_collectible_index', [], Response::HTTP_SEE_OTHER);
    }
}
