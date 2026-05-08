<?php
// src/Controller/ProfileController.php

namespace App\Controller;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/profile')]
class ProfileController extends AbstractController
{
    // ── VIEW PROFILE ──────────────────────────────────────────────────────────
    #[Route('', name: 'app_profile', methods: ['GET'])]
    public function index(): Response
    {
        /** @var Users $user */
        $user = $this->getUser();
        return $this->render('profile/index.html.twig', ['user' => $user]);
    }

    // ── UPDATE NAME & PHONE ───────────────────────────────────────────────────
    #[Route('/update-info', name: 'app_profile_update_info', methods: ['POST'])]
    public function updateInfo(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): Response {
        /** @var Users $user */
        $user = $this->getUser();

        $name  = trim($request->request->get('name', ''));
        $phone = trim($request->request->get('phone', ''));

        $errors = [];

        if (strlen($name) < 3) {
            $errors['name'] = 'Name must be at least 3 characters.';
        } elseif (strlen($name) > 100) {
            $errors['name'] = 'Name must be under 100 characters.';
        }

        if ($phone !== '' && !preg_match('/^[0-9]{8}$/', $phone)) {
            $errors['phone'] = 'Phone must be exactly 8 digits.';
        }

        if (empty($errors)) {
            $user->setName($name);
            $user->setPhone($phone !== '' ? $phone : null);
            $em->flush();
            $this->addFlash('success', 'Profile updated successfully.');
        } else {
            foreach ($errors as $msg) {
                $this->addFlash('error', $msg);
            }
        }

        return $this->redirectToRoute('app_profile');
    }

    // ── CHANGE PROFILE PHOTO ──────────────────────────────────────────────────
    #[Route('/update-photo', name: 'app_profile_update_photo', methods: ['POST'])]
    public function updatePhoto(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        /** @var Users $user */
        $user      = $this->getUser();
        $imageFile = $request->files->get('image');

        if ($imageFile) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($imageFile->getMimeType(), $allowed)) {
                $this->addFlash('error', 'Only JPG, PNG or WEBP images are allowed.');
                return $this->redirectToRoute('app_profile');
            }
            if ($imageFile->getSize() > 2 * 1024 * 1024) {
                $this->addFlash('error', 'Image must be smaller than 2 MB.');
                return $this->redirectToRoute('app_profile');
            }

            // Delete old image if it exists
            $oldImage = $user->getImage();
            if ($oldImage) {
                $oldPath = $this->getParameter('images_directory') . '/' . $oldImage;
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $newFilename = uniqid() . '.' . $imageFile->guessExtension();
            $imageFile->move($this->getParameter('images_directory'), $newFilename);
            $user->setImage($newFilename);
            $em->flush();
            $this->addFlash('success', 'Profile photo updated.');
        }

        return $this->redirectToRoute('app_profile');
    }

    // ── CHANGE PASSWORD ───────────────────────────────────────────────────────
    #[Route('/change-password', name: 'app_profile_change_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        /** @var Users $user */
        $user    = $this->getUser();
        $current = $request->request->get('current_password', '');
        $new     = $request->request->get('new_password', '');
        $confirm = $request->request->get('confirm_password', '');

        if (!$hasher->isPasswordValid($user, $current)) {
            $this->addFlash('error', 'Current password is incorrect.');
            return $this->redirectToRoute('app_profile');
        }

        if (strlen($new) < 6) {
            $this->addFlash('error', 'New password must be at least 6 characters.');
            return $this->redirectToRoute('app_profile');
        }

        if (!preg_match('/^(?=.*[A-Z])(?=.*[0-9])(?=.*[\W_]).+$/', $new)) {
            $this->addFlash('error', 'Password must contain an uppercase letter, a number and a special character.');
            return $this->redirectToRoute('app_profile');
        }

        if ($new !== $confirm) {
            $this->addFlash('error', 'Passwords do not match.');
            return $this->redirectToRoute('app_profile');
        }

        $user->setPassword($hasher->hashPassword($user, $new));
        $em->flush();
        $this->addFlash('success', 'Password changed successfully.');

        return $this->redirectToRoute('app_profile');
    }
}