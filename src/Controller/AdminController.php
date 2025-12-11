<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\CollectibleRepository;
use App\Repository\ListingRepository;
use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(
        UserRepository $userRepo,
        CollectibleRepository $collectibleRepo,
        ListingRepository $listingRepo,
    ): Response {
        $totalUsers = $userRepo->count([]);
        
        $totalStaff = $userRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role_staff')
            ->setParameter('role_staff', '%ROLE_STAFF%')
            ->getQuery()
            ->getSingleScalarResult();
        
        $totalCollectibles = $collectibleRepo->count([]);
        $totalListings = $listingRepo->count([]);
        
        $totalValue = 0;
        try {
            $totalValue = $listingRepo->createQueryBuilder('l')
                ->select('SUM(l.price)')
                ->getQuery()
                ->getSingleScalarResult() ?? 0;
        } catch (\Exception $e) {
        }

        $recentListings = $listingRepo->findBy([], ['id' => 'DESC'], 5);
        $recentUsers = $userRepo->findBy([], ['id' => 'DESC'], 10);

        return $this->render('admin/index.html.twig', [
            'title' => 'Admin Dashboard',
            'totalUsers' => $totalUsers,
            'totalStaff' => $totalStaff,
            'totalCollectibles' => $totalCollectibles,
            'totalListings' => $totalListings,
            'totalValue' => $totalValue,
            'recentListings' => $recentListings,
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