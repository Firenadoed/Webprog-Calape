<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ActivityLogger
{
    private $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    /**
     * Log an action to the database
     */
    public function log(User $user, string $action, ?string $targetData = null): void
    {
        // Only log admin/staff actions (as per rubric)
        $roles = $user->getRoles();
        
        // Get primary role
        $primaryRole = !empty($roles) ? $roles[0] : 'ROLE_USER';
        
        // Get username (you're using username field)
        $username = $user->getUsername();
        
        // Create log entry
        $log = new ActivityLog();
        $log->setUserId($user->getId());
        $log->setUsername($username);
        $log->setRole($primaryRole);
        $log->setAction($action);
        $log->setTargetData($targetData);
        
        // Save to database
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
    
    /**
     * Alternative: Log with user ID instead of User object
     * Useful for logout where user might not be available
     */
    public function logById(int $userId, string $username, string $role, string $action, ?string $targetData = null): void
    {
        $log = new ActivityLog();
        $log->setUserId($userId);
        $log->setUsername($username);
        $log->setRole($role);
        $log->setAction($action);
        $log->setTargetData($targetData);
        
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
    
    /**
     * Helper: Log user login
     */
    public function logLogin(User $user): void
    {
        $this->log($user, 'LOGIN');
    }
    
    /**
     * Helper: Log user logout
     */
    public function logLogout(User $user): void
    {
        $this->log($user, 'LOGOUT');
    }
    
    /**
     * Helper: Log user creation
     */
    public function logUserCreation(User $performedBy, User $createdUser): void
    {
        $this->log($performedBy, 'CREATE_USER', 
            'Created user: ' . $createdUser->getUsername() . ' (ID: ' . $createdUser->getId() . ')'
        );
    }
    
    /**
     * Helper: Log user deletion
     */
    public function logUserDeletion(User $performedBy, string $deletedUsername, int $deletedId): void
    {
        $this->log($performedBy, 'DELETE_USER',
            'Deleted user: ' . $deletedUsername . ' (ID: ' . $deletedId . ')'
        );
    }
    
    /**
     * Helper: Log collection creation
     */
    public function logCollectionCreation(User $user, $collection): void
    {
        $this->log($user, 'CREATE_COLLECTION',
            'Collection: ' . $collection->getTitle() . ' (ID: ' . $collection->getId() . ')'
        );
    }
}