<?php
// src/Controller/UsersController.php

namespace App\Controller;

use App\Entity\Users;
use App\Form\UsersType;
use App\Form\RegistrationFormType;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\FormError;

#[Route('/admin/users')]
final class UsersController extends AbstractController
{
    private function currentUserId(): ?int
    {
        /** @var Users|null $u */
        $u = $this->getUser();
        return $u?->getId();
    }

    // ── INDEX ─────────────────────────────────────────────────────────────────
    #[Route(name: 'app_users_index', methods: ['GET'])]
    public function index(UsersRepository $usersRepository): Response
    {
        return $this->render('users/index.html.twig', [
            'users'           => $usersRepository->findAll(),
            'current_user_id' => $this->currentUserId(),
        ]);
    }

    // ── CREATE via AJAX (called by the modal) ─────────────────────────────────
    #[Route('/create', name: 'app_users_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $user = new Users();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        $plain   = $form->get('password')->getData();
        $confirm = $request->request->get('confirm_password');
        if ($plain && $plain !== $confirm) {
            $form->get('password')->addError(new FormError('Passwords do not match.'));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, $plain));

            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $filename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('images_directory'), $filename);
                $user->setImage($filename);
            }

            $user->setRole('USER');
            $em->persist($user);
            $em->flush();

            return $this->json([
                'success' => true,
                'user' => [
                    'id'        => $user->getId(),
                    'name'      => $user->getName(),
                    'email'     => $user->getEmail(),
                    'role'      => $user->getRole(),
                    'phone'     => $user->getPhone(),
                    'motorized' => $user->getMotorized(),
                    'image'     => $user->getImage(),
                ],
            ]);
        }

        $errors = [];
        foreach ($form->all() as $fieldName => $field) {
            foreach ($field->getErrors() as $error) {
                $errors[$fieldName][] = $error->getMessage();
            }
        }
        foreach ($form->getErrors() as $error) {
            $errors['_form'][] = $error->getMessage();
        }

        return $this->json(['success' => false, 'errors' => $errors], 422);
    }

    // ── NEW (fallback page — not used by modal) ───────────────────────────────
    #[Route('/new', name: 'app_users_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = new Users();
        $form = $this->createForm(UsersType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($user); $em->flush();
            $this->addFlash('success', 'User created.');
            return $this->redirectToRoute('app_users_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('users/new.html.twig', ['user' => $user, 'form' => $form]);
    }

    // ── SHOW ──────────────────────────────────────────────────────────────────
    #[Route('/{id}', name: 'app_users_show', methods: ['GET'])]
    public function show(Users $user): Response
    {
        return $this->render('users/show.html.twig', ['user' => $user]);
    }

    // ── EDIT ──────────────────────────────────────────────────────────────────
    #[Route('/{id}/edit', name: 'app_users_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Users $user, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(UsersType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'User updated.');
            return $this->redirectToRoute('app_users_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('users/edit.html.twig', ['user' => $user, 'form' => $form]);
    }

    // ── DELETE ────────────────────────────────────────────────────────────────
    #[Route('/{id}', name: 'app_users_delete', methods: ['POST'])]
    public function delete(Request $request, Users $user, EntityManagerInterface $em): Response
    {
        if ($user->getId() === $this->currentUserId()) {
            $this->addFlash('error', 'You cannot delete your own account.');
            return $this->redirectToRoute('app_users_index', [], Response::HTTP_SEE_OTHER);
        }
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($user); $em->flush();
            $this->addFlash('success', $user->getName() . ' deleted.');
        }
        return $this->redirectToRoute('app_users_index', [], Response::HTTP_SEE_OTHER);
    }

    // ── PROMOTE ───────────────────────────────────────────────────────────────
    #[Route('/{id}/promote', name: 'app_users_promote', methods: ['GET'])]
    public function promote(Users $user, EntityManagerInterface $em): Response
    {
        $user->setRole('ADMIN'); $em->flush();
        $this->addFlash('success', $user->getName() . ' promoted to Admin.');
        return $this->redirectToRoute('app_users_index', [], Response::HTTP_SEE_OTHER);
    }

    // ── DEMOTE ────────────────────────────────────────────────────────────────
    #[Route('/{id}/demote', name: 'app_users_demote', methods: ['GET'])]
    public function demote(Users $user, EntityManagerInterface $em): Response
    {
        if ($user->getId() === $this->currentUserId()) {
            $this->addFlash('error', 'You cannot demote your own account.');
            return $this->redirectToRoute('app_users_index', [], Response::HTTP_SEE_OTHER);
        }
        $user->setRole('USER'); $em->flush();
        $this->addFlash('success', $user->getName() . ' demoted to User.');
        return $this->redirectToRoute('app_users_index', [], Response::HTTP_SEE_OTHER);
    }

    // ── BAN / UNBAN ───────────────────────────────────────────────────────────
    #[Route('/{id}/ban', name: 'app_users_ban', methods: ['GET'])]
    public function ban(Users $user, EntityManagerInterface $em): Response
    {
        if ($user->getId() === $this->currentUserId()) {
            $this->addFlash('error', 'You cannot ban your own account.');
            return $this->redirectToRoute('app_users_index', [], Response::HTTP_SEE_OTHER);
        }
        if ($user->getRole() === 'BANNED') {
            $user->setRole('USER');
            $this->addFlash('success', $user->getName() . ' unbanned.');
        } else {
            $user->setRole('BANNED');
            $this->addFlash('success', $user->getName() . ' banned.');
        }
        $em->flush();
        return $this->redirectToRoute('app_users_index', [], Response::HTTP_SEE_OTHER);
    }
}
