<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\Users;
use App\Form\PostType;
use App\Repository\PostRepository;
use App\Service\CensorshipService;
use App\Service\TimeAgoService;
use App\Service\TranslationService;
use App\Service\UnsplashService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('ROLE_USER')]
class PostController extends AbstractController
{
    // ── List posts ──
    #[Route('/post', name: 'app_post_index', methods: ['GET'])]
    public function index(PostRepository $postRepository, Request $request): Response
    {
        $search = $request->query->get('search');
        $sort   = $request->query->get('sort', 'latest');
        $posts  = $postRepository->findBySearchAndSort($search, $sort);

        return $this->render('post/index.html.twig', [
            'posts'          => $posts,
            'current_search' => $search,
            'current_sort'   => $sort,
        ]);
    }

    // ── New post ──
    #[Route('/post/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        CensorshipService $censor,
        ValidatorInterface $validator
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $post = new Post();
        $post->setCreatedAt(new \DateTime());
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $validator->validate($post);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('post/new.html.twig', ['post' => $post, 'form' => $form]);
            }

            $post->setTitle($censor->censorText($post->getTitle()));
            $post->setContent($censor->censorText($post->getContent()));
            /** @var Users $author */
            $author = $this->getUser();
            $post->setAuthor($author);

            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('success', 'Post publié avec succès !');
            return $this->redirectToRoute('app_post_index');
        }

        return $this->render('post/new.html.twig', ['post' => $post, 'form' => $form]);
    }

    // ── Edit post ──
    #[Route('/post/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Post $post,
        EntityManagerInterface $entityManager,
        CensorshipService $censor,
        ValidatorInterface $validator
    ): Response {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($post->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas l\'auteur de ce post.');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $validator->validate($post);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('post/edit.html.twig', ['post' => $post, 'form' => $form]);
            }

            $post->setTitle($censor->censorText($post->getTitle()));
            $post->setContent($censor->censorText($post->getContent()));
            $entityManager->flush();

            $this->addFlash('success', 'Post mis à jour avec succès !');
            return $this->redirectToRoute('app_post_index');
        }

        return $this->render('post/edit.html.twig', ['post' => $post, 'form' => $form]);
    }

    // ── Delete post ──
    #[Route('/post/{id}', name: 'app_post_delete', methods: ['POST'])]
    public function delete(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        if ($post->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas l\'auteur de ce post.');
        }

        if ($this->isCsrfTokenValid('delete' . $post->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();
            $this->addFlash('success', 'Post supprimé avec succès !');
        }
        return $this->redirectToRoute('app_post_index');
    }

    // ── Add comment ──
    #[Route('/post/{id}/comment/add', name: 'app_comment_add', methods: ['POST'])]
    public function addComment(
        Request $request,
        Post $post,
        EntityManagerInterface $entityManager,
        CensorshipService $censor,
        ValidatorInterface $validator
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $content     = $request->request->get('content');
        $tempComment = new Comment();
        $tempComment->setContent((string) $content);

        $errors = $validator->validate($tempComment);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            return $this->redirectToRoute('app_post_index');
        }

        if (!empty($content)) {
            $comment = new Comment();
            $comment->setContent($censor->censorText($content));
            $comment->setPost($post);
            /** @var Users $author */
            $author = $this->getUser();
            $comment->setAuthor($author);
            $comment->setCreatedAt(new \DateTime());
            $entityManager->persist($comment);
            $entityManager->flush();
            $this->addFlash('success', 'Commentaire ajouté !');
        }

        return $this->redirectToRoute('app_post_index');
    }

    // ── Delete comment ──
    #[Route('/comment/{id}/delete', name: 'app_comment_delete', methods: ['POST'])]
    public function deleteComment(Comment $comment, EntityManagerInterface $entityManager): Response
    {
        if ($comment->getAuthor() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas l\'auteur de ce commentaire.');
        }

        $entityManager->remove($comment);
        $entityManager->flush();
        $this->addFlash('success', 'Commentaire supprimé !');
        return $this->redirectToRoute('app_post_index');
    }

    // ── Edit comment ──
    #[Route('/comment/{id}/edit', name: 'app_comment_edit', methods: ['POST'])]
    public function editComment(
        Request $request,
        Comment $comment,
        EntityManagerInterface $entityManager,
        CensorshipService $censor,
        ValidatorInterface $validator
    ): Response {
        if ($comment->getAuthor() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas l\'auteur de ce commentaire.');
        }

        $content     = $request->request->get('content');
        $tempComment = new Comment();
        $tempComment->setContent((string) $content);

        $errors = $validator->validate($tempComment);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            return $this->redirectToRoute('app_post_index');
        }

        if (!empty($content)) {
            $comment->setContent($censor->censorText($content));
            $entityManager->flush();
            $this->addFlash('success', 'Commentaire modifié !');
        }
        return $this->redirectToRoute('app_post_index');
    }

    // ── Reaction toggle (Session-based) ──
    #[Route('/post/{id}/reaction/{emoji}', name: 'app_post_reaction', methods: ['POST'])]
    public function toggleReaction(
        Request $request,
        Post $post,
        string $emoji,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $session     = $request->getSession();
        $reactionKey = 'reacted_' . $post->getId() . '_' . $emoji;
        $hasReacted  = $session->get($reactionKey, false);
        $reactions   = $post->getReactions();
        if (!isset($reactions[$emoji])) {
            $reactions[$emoji] = 0;
        }

        if ($hasReacted) {
            if ($reactions[$emoji] > 0) $reactions[$emoji]--;
            $session->set($reactionKey, false);
        } else {
            $reactions[$emoji]++;
            $session->set($reactionKey, true);
        }

        $post->setReactions($reactions);
        $entityManager->flush();

        return $this->json(['reactions' => $post->getReactions()]);
    }

    // ── Translate post content (FR → EN) ──
    #[Route('/post/{id}/translate', name: 'app_post_translate', methods: ['GET'])]
    public function translatePost(Post $post, TranslationService $translator): JsonResponse
    {
        $translatedTitle   = $translator->translate($post->getTitle(), 'fr', 'en');
        $translatedContent = $translator->translate($post->getContent(), 'fr', 'en');

        return $this->json([
            'title'   => $translatedTitle,
            'content' => $translatedContent
        ]);
    }

    // ── Get dynamic Unsplash image for post ──
    #[Route('/post/{id}/image', name: 'app_post_image', methods: ['GET'])]
    public function getPostImage(Post $post, UnsplashService $unsplash): JsonResponse
    {
        $imageUrl = $unsplash->fetchImageUrl($post->getTitle());
        return $this->json(['image_url' => $imageUrl]);
    }
}
