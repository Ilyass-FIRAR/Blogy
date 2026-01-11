<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        // Redirect if already logged in
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            // Validate CSRF token
            if (!$this->isCsrfTokenValid('register', $request->request->get('_csrf_token'))) {
                $error = 'Invalid CSRF token.';
            } else {
                $email = trim((string) $request->request->get('email'));
                $username = trim((string) $request->request->get('username'));
                $password = (string) $request->request->get('password');
                $confirmPassword = (string) $request->request->get('confirm_password');

                // Basic validation
                if (empty($email) || empty($username) || empty($password) || empty($confirmPassword)) {
                    $error = 'All fields are required.';
                } elseif ($password !== $confirmPassword) {
                    $error = 'Passwords do not match.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Invalid email address.';
                } elseif ($entityManager->getRepository(User::class)->findOneBy(['email' => $email])) {
                    $error = 'An account with this email already exists.';
                    //            $this->addFlash('error', 'email Already exists.');
                } elseif ($entityManager->getRepository(User::class)->findOneBy(['username' => $username])) {
                    $error = 'This username is already taken.';
                    //            $this->addFlash('error', 'email Already exists.');
                } else {
                    // Create new user
                    $user = new User();
                    $user->setEmail($email);
                    $user->setUsername($username);

                    $normalizedEmail = strtolower($email);
                    $roles = ['ROLE_USER'];
                    if (in_array($normalizedEmail, User::ADMIN_EMAILS, true)) {
                        $roles[] = 'ROLE_ADMIN';
                    }
                    $user->setRoles(array_unique($roles));

                    // Hash the password
                    $hashedPassword = $passwordHasher->hashPassword($user, $password);
                    $user->setPassword($hashedPassword);

                    try {
                        $entityManager->persist($user);
                        $entityManager->flush();

                        // Redirect to login page after successful registration
                        return $this->redirectToRoute('app_login');
                    } catch (UniqueConstraintViolationException) {
                        // Database-level unique constraint hit (email/username already exists)
                        $error = 'An account with this email or username already exists.';
                    }
                }
            }
        }

        if ($error) {
            $this->addFlash('error', $error);
        }

        return $this->render('registration/register.html.twig', [
            'error' => $error,
        ]);
    }
}