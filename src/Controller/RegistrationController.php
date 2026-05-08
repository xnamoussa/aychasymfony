<?php
// src/Controller/RegistrationController.php

namespace App\Controller;

use App\Entity\Users;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\FormError;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {

        if ($this->getUser()) {
            return $this->redirectToRoute('app_users_index');
        }

        $user = new Users();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            // ── Manual reCAPTCHA v2 verification ───────────────────────────────
            $recaptchaResponse = $request->request->get('g-recaptcha-response');
            $secretKey = $_ENV['EWZ_RECAPTCHA_SECRET'] ?? '';
            
            // Simple validation call to Google
            $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$secretKey.'&response='.$recaptchaResponse);
            $responseData = json_decode($verifyResponse);
            
            if (!$responseData || !$responseData->success) {
                $form->addError(new FormError('Please complete the CAPTCHA checkbox.'));
            }

            // ── Confirm password check ───────────────────────────────────────
            $plainPassword   = $form->get('password')->getData();
            $confirmPassword = $request->request->get('confirm_password');

            if ($plainPassword !== $confirmPassword) {
                $form->get('password')->addError(
                    new FormError('Passwords do not match.')
                );
            }

            if ($form->isValid()) {

                // ── Hash the password ────────────────────────────────────────
                $hashed = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashed);

                // ── Handle image upload ──────────────────────────────────────
                $imageFile = $form->get('imageFile')->getData();
                if ($imageFile) {
                    $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                    $user->setImage($newFilename);
                }

                // ── Default role (not in the public form) ────────────────────
                $user->setRole('USER');

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Account created! You can now sign in.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}