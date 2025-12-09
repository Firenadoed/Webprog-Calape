<?php

namespace App\Controller;

use App\Entity\Collectible;
use App\Entity\Listing;
use App\Form\CollectibleType;
use App\Form\AddCollectibleType;
use App\Repository\CollectibleRepository;
use App\Repository\ListingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/collection', name: 'collection')]
    public function collection(
        CollectibleRepository $collectibleRepository,
        ListingRepository $listingRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            $userRepository = $collectibleRepository->getEntityManager()->getRepository(\App\Entity\User::class);
            $user = $userRepository->find(1);
        }

        $userCollectibles = $collectibleRepository->findBy(['user' => $user]);

        $cards = array_filter($userCollectibles, fn($c) => strtolower($c->getCategory()) === 'cards');
        $figures = array_filter($userCollectibles, fn($c) => strtolower($c->getCategory()) === 'figures');
        $games = array_filter($userCollectibles, fn($c) => strtolower($c->getCategory()) === 'games');
        $artworks = array_filter($userCollectibles, fn($c) => strtolower($c->getCategory()) === 'artworks');
        $others = array_filter($userCollectibles, fn($c) => 
            !in_array(strtolower($c->getCategory()), ['cards', 'figures', 'games', 'artworks'])
        );

        $allListings = $listingRepository->findBy(['is_for_sale' => true]);
        $listingMap = [];
        foreach ($allListings as $listing) {
            $listingMap[$listing->getCollectible()->getId()] = $listing->getId();
        }

        return $this->render('home/collection.html.twig', [
            'cards' => $cards,
            'figures' => $figures,
            'games' => $games,
            'artworks' => $artworks,
            'others' => $others,
            'listingMap' => $listingMap,
        ]);
    }

    #[Route('/collection/add', name: 'collection_add')]
    public function add(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $collectible = new Collectible();
        
        $form = $this->createForm(AddCollectibleType::class, $collectible);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            $fetchedImageUrl = $request->request->get('fetched_image');

            if (!$imageFile && $fetchedImageUrl) {
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
                            true
                        );
                    }
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Failed to download the fetched image.');
                }
            }

            if ($imageFile) {
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

            $user = $this->getUser();
            if (!$user) {
                $userRepository = $em->getRepository(\App\Entity\User::class);
                $user = $userRepository->find(1);
            }
            $collectible->setUser($user);
            
            $em->persist($collectible);
            $em->flush();

            $this->addFlash('success', 'Collectible added successfully!');
            return $this->redirectToRoute('collection');
        }

        return $this->render('home/add_collectible.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/explore', name: 'explore')]
    public function explore(
        ListingRepository $listingRepository,
        CollectibleRepository $collectibleRepository,
        Request $request
    ): Response {
        $category = $request->query->get('category');
        $franchise = $request->query->get('franchise');
        $search = $request->query->get('search');

        $categoriesResult = $collectibleRepository->createQueryBuilder('c')
            ->select('DISTINCT c.category')
            ->where('c.category IS NOT NULL')
            ->orderBy('c.category', 'ASC')
            ->getQuery()
            ->getResult();
        $categories = array_column($categoriesResult, 'category');

        $franchisesResult = $collectibleRepository->createQueryBuilder('c')
            ->select('DISTINCT c.franchise')
            ->where('c.franchise IS NOT NULL')
            ->orderBy('c.franchise', 'ASC')
            ->getQuery()
            ->getResult();
        $franchises = array_column($franchisesResult, 'franchise');

        $qb = $listingRepository->createQueryBuilder('l')
            ->innerJoin('l.collectible', 'c')
            ->where('l.is_for_sale = true');

        if ($category) {
            $qb->andWhere('c.category = :category')
               ->setParameter('category', $category);
        }

        if ($franchise) {
            $qb->andWhere('c.franchise = :franchise')
               ->setParameter('franchise', $franchise);
        }

        if ($search) {
            $qb->andWhere('c.name LIKE :search OR c.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $listings = $qb->getQuery()->getResult();

        return $this->render('home/explore.html.twig', [
            'listings' => $listings,
            'categories' => $categories,
            'franchises' => $franchises,
        ]);
    }

    #[Route('/listings/{id}', name: 'listing_show')]
    public function show(
        Listing $listing,
        ListingRepository $listingRepository
    ): Response {
        $relatedListings = $listingRepository->createQueryBuilder('l')
            ->innerJoin('l.collectible', 'c')
            ->where('c.category = :category')
            ->andWhere('l.id != :currentId')
            ->andWhere('l.is_for_sale = true')
            ->setParameter('category', $listing->getCollectible()->getCategory())
            ->setParameter('currentId', $listing->getId())
            ->setMaxResults(4)
            ->getQuery()
            ->getResult();

        return $this->render('home/listing_show.html.twig', [
            'listing' => $listing,
            'relatedListings' => $relatedListings,
        ]);
    }

    #[Route('/collection/delete/{id}', name: 'collectible_delete', methods: ['POST'])]
    public function delete(Collectible $collectible, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete'.$collectible->getId(), $token)) {
            return new JsonResponse(['status'=>'error','message'=>'Invalid CSRF token']);
        }

        $user = $this->getUser();
        if (!$user) {
            $userRepository = $em->getRepository(\App\Entity\User::class);
            $user = $userRepository->find(1);
        }

        $em->remove($collectible);
        $em->flush();

        return new JsonResponse(['status'=>'success','message'=>'Collectible deleted']);
    }

    #[Route('/profile/edit/ajax', name: 'edit_profile_ajax', methods: ['POST'])]
    public function editProfileAjax(
        Request $request, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): JsonResponse {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }
        
        $username = trim($request->request->get('username', ''));
        $bio = trim($request->request->get('bio', ''));
        $profileImageFile = $request->files->get('profileImage');
        $currentPassword = $request->request->get('currentPassword');
        $newPassword = $request->request->get('newPassword');
        $confirmPassword = $request->request->get('confirmPassword');
        
        if ($currentPassword || $newPassword || $confirmPassword) {
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'All password fields are required to change password'
                ]);
            }
            
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ]);
            }
            
            if (strlen($newPassword) < 8) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'New password must be at least 8 characters long'
                ]);
            }
            
            if (!preg_match('/[a-zA-Z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Password must contain both letters and numbers'
                ]);
            }
            
            if ($newPassword !== $confirmPassword) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'New passwords do not match'
                ]);
            }
            
            if ($passwordHasher->isPasswordValid($user, $newPassword)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'New password cannot be the same as current password'
                ]);
            }
            
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            
            try {
                $entityManager->flush();
                
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Password changed successfully!'
                ]);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'An error occurred while changing password: ' . $e->getMessage()
                ]);
            }
        }
        
        if (empty($username)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Username is required'
            ]);
        }
        
        $existingUser = $entityManager->getRepository(\App\Entity\User::class)->findOneBy(['username' => $username]);
        if ($existingUser && $existingUser->getId() !== $user->getId()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Username already exists. Please choose a different one.'
            ]);
        }
        
        $user->setUsername($username);
        $user->setBio($bio);
        
        if ($profileImageFile) {
            if ($profileImageFile->getSize() > 2 * 1024 * 1024) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'File size must be less than 2MB'
                ]);
            }
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($profileImageFile->getMimeType(), $allowedTypes)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Invalid file type. Please upload JPG, PNG, GIF, or WebP'
                ]);
            }
            
            $originalFilename = pathinfo($profileImageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$profileImageFile->guessExtension();
            
            try {
                $profileImageFile->move(
                    $this->getParameter('profile_images_directory'),
                    $newFilename
                );
                
                $oldImage = $user->getProfileImage();
                if ($oldImage && $oldImage !== 'default.png' && file_exists($this->getParameter('profile_images_directory').'/'.$oldImage)) {
                    unlink($this->getParameter('profile_images_directory').'/'.$oldImage);
                }
                
                $user->setProfileImage($newFilename);
            } catch (FileException $e) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Could not upload image. Please try again.'
                ]);
            }
        }
        
        try {
            $entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Profile updated successfully!',
                'user' => [
                    'username' => $user->getUsername(),
                    'bio' => $user->getBio(),
                    'profileImage' => $user->getProfileImage(),
                    'createdAt' => $user->getCreatedAt()->format('F Y')
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred while saving your profile: ' . $e->getMessage()
            ]);
        }
    }
   
}