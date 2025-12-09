<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/user')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
public function new(
    Request $request, 
    EntityManagerInterface $entityManager,
    UserPasswordHasherInterface $passwordHasher,
    SluggerInterface $slugger,
    ActivityLogger $logger
): Response
{
    $user = new User();
    $form = $this->createForm(UserType::class, $user, ['is_edit' => false]);
    $form->handleRequest($request);

    // ✔ Correct Symfony validation pattern
    if ($form->isSubmitted() && $form->isValid()) {

        // Handle password hashing for new user
        $password = $form->get('password')->getData();
        if ($password && trim($password) !== '') {
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
        } else {
            $this->addFlash('error', 'Password is required for new users');
            return $this->render('user/new.html.twig', [
                'user' => $user,
                'form' => $form->createView(),
            ]);
        }

        // Handle role from dropdown
        $selectedRole = $form->get('roles')->getData();
        $user->setRoles([$selectedRole]);

        // Handle profile image upload
        $profileImageFile = $form->get('profile_image')->getData();
        if ($profileImageFile) {
            $originalFilename = pathinfo($profileImageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$profileImageFile->guessExtension();

            try {
                $profileImageFile->move(
                    $this->getParameter('kernel.project_dir').'/public/uploads/profile',
                    $newFilename
                );
                $user->setProfileImage($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                $user->setProfileImage('default.jfif');
            }
        } else {
            // Default image if none uploaded
            $user->setProfileImage('default.jfif');
        }

        $entityManager->persist($user);
        $entityManager->flush();

        // LOG USER CREATION
        $currentUser = $this->getUser();
        $logger->log($currentUser, 'CREATE_USER', 
            'Created user: ' . $user->getUsername() . ' (ID: ' . $user->getId() . ') with role: ' . $selectedRole
        );

        $this->addFlash('success', 'User created successfully!');
        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    // ❗ If form is submitted but invalid → Symfony automatically shows all errors
    // (including UniqueEntity errors) in the Twig template
    return $this->render('user/new.html.twig', [
        'user' => $user,
        'form' => $form->createView(),
    ]);
}

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        User $user, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger,
        ActivityLogger $logger
    ): Response
    {
        // Get current role for pre-selection
        $currentRoles = $user->getRoles();
        $currentRole = $currentRoles[0] ?? 'ROLE_USER';
        
        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        
        // Pre-select current role in dropdown
        $form->get('roles')->setData($currentRole);
        
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Handle password if changed - only update if not empty
                $password = $form->get('password')->getData();
                if ($password && trim($password) !== '') {
                    $hashedPassword = $passwordHasher->hashPassword($user, $password);
                    $user->setPassword($hashedPassword);
                    $this->addFlash('info', 'Password was updated');
                }
                // If password is empty, keep existing password (DO NOTHING)

                // Update role
                $selectedRole = $form->get('roles')->getData();
                $user->setRoles([$selectedRole]);

                // Handle profile image upload if new image provided
                $profileImageFile = $form->get('profile_image')->getData();
                if ($profileImageFile) {
                    $originalFilename = pathinfo($profileImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$profileImageFile->guessExtension();

                    try {
                        $profileImageFile->move(
                            $this->getParameter('kernel.project_dir').'/public/uploads/profile',
                            $newFilename
                        );
                        
                        // Remove old image if not default
                        $oldImage = $user->getProfileImage();
                        if ($oldImage && $oldImage !== 'default.jfif') {
                            $oldImagePath = $this->getParameter('kernel.project_dir').'/public/uploads/profile/'.$oldImage;
                            if (file_exists($oldImagePath)) {
                                unlink($oldImagePath);
                            }
                        }
                        
                        $user->setProfileImage($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                    }
                }
                // If no new image uploaded, keep existing image

                $entityManager->flush();

                // LOG USER UPDATE
                $currentUser = $this->getUser();
                $logger->log($currentUser, 'UPDATE_USER', 
                    'Updated user: ' . $user->getUsername() . ' (ID: ' . $user->getId() . ') - New role: ' . $selectedRole
                );

                $this->addFlash('success', 'User updated successfully!');
                return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
            } else {
                // Form validation failed
                $this->addFlash('error', 'Please fix the validation errors below.');
            }
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        User $user, 
        EntityManagerInterface $entityManager,
        ActivityLogger $logger
    ): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            // Store user info BEFORE deletion
            $username = $user->getUsername();
            $userId = $user->getId();
            
            // LOG USER DELETION (BEFORE deleting!)
            $currentUser = $this->getUser();
            $logger->log($currentUser, 'DELETE_USER', 
                'Deleted user: ' . $username . ' (ID: ' . $userId . ')'
            );
            
            // Remove profile image file if not default
            $profileImage = $user->getProfileImage();
            if ($profileImage && $profileImage !== 'default.jfif') {
                $imagePath = $this->getParameter('kernel.project_dir').'/public/uploads/profile/'.$profileImage;
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            $entityManager->remove($user);
            $entityManager->flush();
            
            $this->addFlash('success', 'User deleted successfully!');
        } else {
            // Invalid CSRF token
            $this->addFlash('error', 'Invalid security token. Please try again.');
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}