<?php

namespace App\Controller;

use App\Entity\Collectible;
use App\Entity\Listing;
use App\Form\CollectibleType;
use App\Repository\CollectibleRepository;
use App\Repository\ListingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'message' => 'Hello Symfony! 🚀',
        ]);
    }

    #[Route('/collection', name: 'collection')]
    public function collection(
        CollectibleRepository $collectibleRepository,
        ListingRepository $listingRepository
    ): Response {
        // Helper to fetch collectibles by category
        $fetchByCategory = function (string $category) use ($collectibleRepository) {
            return $collectibleRepository->createQueryBuilder('c')
                ->join('c.category', 'cat')
                ->where('cat.name = :categoryName')
                ->setParameter('categoryName', $category)
                ->getQuery()
                ->getResult();
        };

        $cards = $fetchByCategory('Cards');
        $figures = $fetchByCategory('Figures');
        $games = $fetchByCategory('Games');
        $others = $fetchByCategory('Others');

        // Build a map of collectible_id => listing_id for items that are currently for sale
        $allListings = $listingRepository->findBy(['is_for_sale' => true]);
        $listingMap = [];
        foreach ($allListings as $listing) {
            $listingMap[$listing->getCollectible()->getId()] = $listing->getId();
        }

        return $this->render('home/collection.html.twig', [
            'cards' => $cards,
            'figures' => $figures,
            'games' => $games,
            'others' => $others,
            'listingMap' => $listingMap, // Pass the map to Twig
        ]);
    }

    #[Route('/collection/add', name: 'collection_add')]
    public function add(
        Request $request,
        EntityManagerInterface $em,
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
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('collectibles_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Image upload failed.');
                }

                $collectible->setImage($newFilename);
            }

            // Link collectible to logged-in user if available
            $user = $this->getUser();
            if ($user) {
                $collectible->setUser($user);
            }

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
    $search = $request->query->get('search');

    // Get all categories for the dropdown
    $categories = $collectibleRepository->createQueryBuilder('c')
       ->join('c.category', 'cat')
        ->select('DISTINCT cat.name')
        ->getQuery()
        ->getScalarResult(); // returns array of ['name' => 'Cards'], etc.
    $qb = $listingRepository->createQueryBuilder('l')
        ->join('l.collectible', 'c')
        ->join('c.category', 'cat');

    if ($category) {
        $qb->andWhere('cat.name = :category')
           ->setParameter('category', $category);
    }

    if ($search) {
        $qb->andWhere('c.name LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }

    $listings = $qb->getQuery()->getResult();

  return $this->render('home/explore.html.twig', [
    'listings' => $listings,
    'categories' => $categories,
]);
}
//   #[Route('/listing/{id}', name: 'listing_show')]
// public function show(Listing $listing): Response
// {
//     return $this->render('home/listing_show.html.twig', [
//         'listing' => $listing,
//     ]);
// }

}
