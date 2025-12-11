<?php

namespace App\Controller;

use App\Entity\Collectible;
use App\Entity\Order;
use App\Entity\Listing;
use App\Form\CollectibleType;
use App\Form\AddCollectibleType;
use App\Repository\CollectibleRepository;
use App\Repository\OrderRepository;
use App\Repository\ListingRepository;
use App\Service\ActivityLogger;
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
        ListingRepository $listingRepository,
        OrderRepository $orderRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            $userRepository = $collectibleRepository->getEntityManager()->getRepository(\App\Entity\User::class);
            $user = $userRepository->find(1);
        }

        // Get user's collectibles grouped by category
        $userCollectibles = $collectibleRepository->findBy(['user' => $user]);

        $cards = array_filter($userCollectibles, fn($c) => strtolower($c->getCategory()) === 'cards');
        $figures = array_filter($userCollectibles, fn($c) => strtolower($c->getCategory()) === 'figures');
        $games = array_filter($userCollectibles, fn($c) => strtolower($c->getCategory()) === 'games');
        $artworks = array_filter($userCollectibles, fn($c) => strtolower($c->getCategory()) === 'artworks');
        $others = array_filter($userCollectibles, fn($c) => 
            !in_array(strtolower($c->getCategory()), ['cards', 'figures', 'games', 'artworks'])
        );

        // Get listing map for collectibles
        $allListings = $listingRepository->findBy(['is_for_sale' => true]);
        $listingMap = [];
        foreach ($allListings as $listing) {
            if ($listing->getCollectible()) {
                $listingMap[$listing->getCollectible()->getId()] = $listing->getId();
            }
        }

        // Get user's active listings
        $myListings = $listingRepository->findBy([
            'user' => $user,
            'is_for_sale' => true
        ], ['id' => 'DESC']);

        // Get incoming orders (orders for user's listings)
        $incomingOrders = $orderRepository->createQueryBuilder('o')
            ->join('o.listing', 'l')
            ->where('l.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.orderedAt', 'DESC')
            ->getQuery()
            ->getResult();

        // Get user's own orders (orders placed by user)
        $myOrders = $orderRepository->findBy([
            'buyer' => $user
        ], ['orderedAt' => 'DESC']);

        return $this->render('home/collection.html.twig', [
            'cards' => $cards,
            'figures' => $figures,
            'games' => $games,
            'artworks' => $artworks,
            'others' => $others,
            'listingMap' => $listingMap,
            'my_listings' => $myListings,
            'incoming_orders' => $incomingOrders,
            'my_orders' => $myOrders,
        ]);
    }


    #[Route('/collection/add', name: 'collection_add')]
    public function add(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        ActivityLogger $logger
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

            // Log collectible creation
            $logger->log(
                $user,
                'CREATE_COLLECTIBLE',
                'Created collectible: ' . $collectible->getName() . 
                ' (ID: ' . $collectible->getId() . ') - ' .
                'Category: ' . $collectible->getCategory() . ' - ' .
                'Franchise: ' . $collectible->getFranchise()
            );

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
    public function delete(
        Collectible $collectible, 
        Request $request, 
        EntityManagerInterface $em,
        ActivityLogger $logger
    ): JsonResponse
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

        // Log before deletion
        $logger->log(
            $user,
            'DELETE_COLLECTIBLE',
            'Deleted collectible: ' . $collectible->getName() . 
            ' (ID: ' . $collectible->getId() . ') - ' .
            'Category: ' . $collectible->getCategory() . ' - ' .
            'Franchise: ' . $collectible->getFranchise()
        );

        $em->remove($collectible);
        $em->flush();

        return new JsonResponse(['status'=>'success','message'=>'Collectible deleted']);
    }

    #[Route('/profile/edit/ajax', name: 'edit_profile_ajax', methods: ['POST'])]
    public function editProfileAjax(
        Request $request, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger,
        ActivityLogger $logger
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
        
        // Track changes for logging
        $changes = [];
        
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
            $changes[] = 'password changed';
            
            try {
                $entityManager->flush();
                
                // Log password change
                $logger->log(
                    $user,
                    'UPDATE_PROFILE',
                    'User changed password'
                );
                
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
        
        // Check for username change
        if ($user->getUsername() !== $username) {
            $changes[] = 'username changed from "' . $user->getUsername() . '" to "' . $username . '"';
            $user->setUsername($username);
        }
        
        // Check for bio change
        if ($user->getBio() !== $bio) {
            $changes[] = 'bio updated';
            $user->setBio($bio);
        }
        
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
                $changes[] = 'profile image updated';
            } catch (FileException $e) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Could not upload image. Please try again.'
                ]);
            }
        }
        
        if (empty($changes)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No changes were made.'
            ]);
        }
        
        try {
            $entityManager->flush();
            
            // Log profile update
            $logger->log(
                $user,
                'UPDATE_PROFILE',
                'User updated profile: ' . implode(', ', $changes)
            );
            
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
   // In HomeController.php
#[Route('/my-listings', name: 'my_listings')]
public function myListings(
    ListingRepository $listingRepository,
    OrderRepository $orderRepository
): Response
{
    $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
    
    $user = $this->getUser();
    
    // Get user's active listings
    $myListings = $listingRepository->findBy([
        'user' => $user,
        'is_for_sale' => true
    ], ['id' => 'DESC']);
    
    // Get incoming orders (orders for user's listings)
    $incomingOrders = $orderRepository->createQueryBuilder('o')
        ->join('o.listing', 'l')
        ->where('l.user = :user')
        ->setParameter('user', $user)
        ->orderBy('o.orderedAt', 'DESC')
        ->getQuery()
        ->getResult();
    
    // Get user's purchases (orders where user is the buyer)
    $myPurchases = $orderRepository->createQueryBuilder('o')
        ->where('o.buyer = :user')
        ->setParameter('user', $user)
        ->orderBy('o.orderedAt', 'DESC')
        ->getQuery()
        ->getResult();
    
    return $this->render('home/my_listings.html.twig', [
        'my_listings' => $myListings,
        'incoming_orders' => $incomingOrders,
        'my_purchases' => $myPurchases, // Add this line
    ]);
}
}