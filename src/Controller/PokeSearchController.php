<?php

namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Collectible;
use App\Form\AddCollectibleType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;  // CORRECT NAMESPACE
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;  // ADD THIS

final class PokeSearchController extends AbstractController
{
    #[Route('/pokesearch', name: 'pokesearch_trial')]
    public function index(): Response
    {
        return $this->render('pokesearch/pokesearch.html.twig', [
            'controller_name' => 'TrialController',
        ]);
    }
     #[Route('/collection/edit/{id}', name: 'collectible_edit')]
public function edit(
    Collectible $collectible,
    Request $request,
    EntityManagerInterface $em,
    SluggerInterface $slugger
): Response {
    // Use the new user-facing form type (same as add)
    $form = $this->createForm(AddCollectibleType::class, $collectible);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $imageFile = $form->get('image')->getData();

        // Check if there's a fetched image URL from JS
        $fetchedImageUrl = $request->request->get('fetched_image');

        if (!$imageFile && $fetchedImageUrl) {
            // Download the image from the URL
            try {
                $imageContents = file_get_contents($fetchedImageUrl);
                if ($imageContents !== false) {
                    $tmpFile = tempnam(sys_get_temp_dir(), 'collectible_');
                    file_put_contents($tmpFile, $imageContents);

                    $imageFile = new \Symfony\Component\HttpFoundation\File\UploadedFile(
                        $tmpFile,
                        basename($fetchedImageUrl),
                        mime_content_type($tmpFile),
                        null,
                        true // mark as "test" to bypass move restrictions
                    );
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to download the fetched image.');
            }
        }

        // If we have a new image (user-uploaded or fetched), process it
        if ($imageFile) {
            // Delete old image if it exists
            $oldImage = $collectible->getImage();
            if ($oldImage) {
                $imagePath = $this->getParameter('collectibles_images_directory') . '/' . $oldImage;
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('collectibles_images_directory'),
                    $newFilename
                );
                $collectible->setImage($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Image upload failed.');
            }
        }

        $em->persist($collectible);
        $em->flush();

        $this->addFlash('success', 'Collectible updated successfully!');
        return $this->redirectToRoute('collection');
    }

    return $this->render('home/edit_collectible.html.twig', [
        'form' => $form->createView(),
        'collectible' => $collectible,
    ]);
}
}
