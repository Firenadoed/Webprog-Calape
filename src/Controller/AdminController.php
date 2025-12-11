<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\CollectibleRepository;
use App\Repository\ListingRepository;
use App\Repository\OrderRepository;
use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function index(
        UserRepository $userRepo,
        CollectibleRepository $collectibleRepo,
        ListingRepository $listingRepo,
        OrderRepository $orderRepo
    ): Response {
        // Check if user has access
        if (!$this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_home');
        }

        // User statistics
        $totalUsers = $userRepo->count([]);
        
        $totalStaff = $userRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role_staff OR u.roles LIKE :role_admin')
            ->setParameter('role_staff', '%ROLE_STAFF%')
            ->setParameter('role_admin', '%ROLE_ADMIN%')
            ->getQuery()
            ->getSingleScalarResult();
        
        // Collectible and Listing statistics
        $totalCollectibles = $collectibleRepo->count([]);
        $totalListings = $listingRepo->count([]);
        
        // Order statistics
        $totalOrders = $orderRepo->count([]);
        $pendingOrdersCount = $orderRepo->count([
            'status' => ['pending', 'confirmed']
        ]);
        
        // Get pending orders for the table (only pending and confirmed)
        $pendingOrders = $orderRepo->createQueryBuilder('o')
            ->leftJoin('o.buyer', 'b')
            ->leftJoin('o.seller', 's')
            ->leftJoin('o.createdBy', 'c')
            ->where('o.status IN (:statuses)')
            ->setParameter('statuses', ['pending', 'confirmed'])
            ->orderBy('o.orderedAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Recent users (for admin only)
        $recentUsers = $userRepo->findBy([], ['createdAt' => 'DESC'], 10);

        return $this->render('dashboard.html.twig', [
            'title' => 'Admin Dashboard',
            'totalUsers' => $totalUsers,
            'totalStaff' => $totalStaff,
            'totalCollectibles' => $totalCollectibles,
            'totalListings' => $totalListings,
            'totalOrders' => $totalOrders,
            'pendingOrdersCount' => $pendingOrdersCount,
            'pendingOrders' => $pendingOrders,
            'recentUsers' => $recentUsers,
        ]);
    }
    
    #[Route('/admin/logs', name: 'admin_logs_index')]
    public function logs(ActivityLogRepository $logRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $logs = $logRepository->findBy([], ['created_at' => 'DESC']);
        
        return $this->render('admin/logs.html.twig', [
            'logs' => $logs,
            'filters' => [],
        ]);
    }

    #[Route('/admin/logs/filter', name: 'admin_logs_filter')]
    public function filterLogs(
        ActivityLogRepository $logRepository, 
        Request $request
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $user = $request->query->get('user');
        $action = $request->query->get('action');
        $date = $request->query->get('date');
        
        $logs = $logRepository->findWithFilters($user, $action, $date);
        
        return $this->render('admin/logs.html.twig', [
            'logs' => $logs,
            'filters' => [
                'user' => $user,
                'action' => $action,
                'date' => $date,
            ]
        ]);
    }
}