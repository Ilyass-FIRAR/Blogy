<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'app_profile')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/edit', name: 'app_profile_edit')]
    public function edit(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/update', name: 'app_profile_update', methods: ['POST'])]
    public function update(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        SluggerInterface $slugger
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Validate CSRF token
        if (!$this->isCsrfTokenValid('profile_update', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_profile_edit');
        }

        // Update username if provided
        $username = $request->request->get('username');
        if ($username && $username !== $user->getUsername()) {
            $user->setUsername($username);
        }

        // Update email if provided
        $email = $request->request->get('email');
        if ($email && $email !== $user->getEmail()) {
            $user->setEmail($email);
        }

        // Handle profile picture upload
        /** @var UploadedFile $profilePictureFile */
        $profilePictureFile = $request->files->get('profile_picture');
        
        if ($profilePictureFile) {
            // Validate file size (max 5MB)
            if ($profilePictureFile->getSize() > 5 * 1024 * 1024) {
                $this->addFlash('error', 'File size must not exceed 5MB.');
                return $this->redirectToRoute('app_profile_edit');
            }

            // Validate file type
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($profilePictureFile->getMimeType(), $allowedMimeTypes)) {
                $this->addFlash('error', 'Only JPG, PNG, GIF, and WEBP images are allowed.');
                return $this->redirectToRoute('app_profile_edit');
            }

            $originalFilename = pathinfo($profilePictureFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$profilePictureFile->guessExtension();

            try {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles';
                
                // Ensure directory exists
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $profilePictureFile->move($uploadDir, $newFilename);
                
                // Delete old profile picture if exists
                if ($user->getProfilePicture()) {
                    $oldFile = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles/' . $user->getProfilePicture();
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }
                
                $user->setProfilePicture($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Failed to upload profile picture: ' . $e->getMessage());
            }
        }

        // Validate the user entity
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            return $this->redirectToRoute('app_profile_edit');
        }

        $entityManager->flush();

        $this->addFlash('success', 'Profile updated successfully!');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/delete-picture', name: 'app_profile_delete_picture', methods: ['POST'])]
    public function deletePicture(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Validate CSRF token
        if (!$this->isCsrfTokenValid('delete_picture', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_profile_edit');
        }

        if ($user->getProfilePicture()) {
            $oldFile = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles/' . $user->getProfilePicture();
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
            $user->setProfilePicture(null);
            $entityManager->flush();
            $this->addFlash('success', 'Profile picture deleted successfully!');
        }

        return $this->redirectToRoute('app_profile_edit');
    }
}
