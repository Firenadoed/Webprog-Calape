<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\CollectibleRepository;
use App\Repository\ListingRepository;
use App\Repository\CategoryRepository;
use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(
        UserRepository $userRepo,
        CollectibleRepository $collectibleRepo,
        ListingRepository $listingRepo,
        CategoryRepository $categoryRepo,
        ActivityLogRepository $activityLogRepo
    ): Response {
        // --- Basic totals ---
        $totalUsers = $userRepo->count([]);
        $totalCollectibles = $collectibleRepo->count([]);
        $totalListings = $listingRepo->count([]);
        $totalCategories = $categoryRepo->count([]);

        // --- Total value of listings ---
        $totalValue = $listingRepo->createQueryBuilder('l')
            ->select('SUM(l.price)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // --- Most popular category ---
        $mostPopularCategory = $collectibleRepo->createQueryBuilder('c')
            ->select('cat.name AS name, COUNT(c.id) AS collectibleCount')
            ->join('c.category', 'cat')
            ->groupBy('cat.id')
            ->orderBy('collectibleCount', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        // --- Recent listings (limit 5) ---
        $recentListings = $listingRepo->findBy([], ['createdAt' => 'DESC'], 5);

        // --- Recent activity log (limit 10) ---
        $recentActivities = $activityLogRepo->createQueryBuilder('a')
            ->leftJoin('a.user', 'U')
            ->addSelect('U')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $this->render('admin/index.html.twig', [
            'title' => 'Admin Dashboard',
            'totalUsers' => $totalUsers,
            'totalCollectibles' => $totalCollectibles,
            'totalListings' => $totalListings,
            'totalCategories' => $totalCategories,
            'totalValue' => $totalValue,
            'mostPopularCategory' => $mostPopularCategory,
            'recentListings' => $recentListings,
            'recentActivities' => $recentActivities, // <<< added here
        ]);
    }
}
