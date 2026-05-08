<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Blog admin management — integrated from FirstProject.
 * Routes kept under /admin/blog/ to avoid collision with the
 * existing AdminController (/admin → app_admin_dashboard).
 */
#[IsGranted('ROLE_ADMIN')]
class BlogAdminController extends AbstractController
{
    // ── Blog overview stats ──
    #[Route('/admin/blog', name: 'admin_blog_dashboard')]
    public function dashboard(PostRepository $postRepository, CommentRepository $commentRepository): Response
    {
        // Use count() instead of findAll() to avoid loading all entities
        $totalPosts    = $postRepository->count([]);
        $totalComments = $commentRepository->count([]);
        $latestPosts   = $postRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $latestComments = $commentRepository->findBy([], ['createdAt' => 'DESC'], 5);

        // DTO-hydrated top posts by comment count — no PHP-side sorting needed
        $topPostDtos = $postRepository->getTopPostsByComments(5);

        // Posts per month (last 6 months) for bar chart — DTO aggregation
        $postsByMonthDtos = $postRepository->getPostsPerMonth();
        $postsByMonthMap  = [];
        foreach ($postsByMonthDtos as $dto) {
            $postsByMonthMap[$dto->label] = (int) $dto->total;
        }

        $months     = [];
        $postCounts = [];
        for ($i = 5; $i >= 0; $i--) {
            $month      = (new \DateTime())->modify("-$i months")->format('Y-m');
            $label      = (new \DateTime())->modify("-$i months")->format('M Y');
            $months[]   = $label;
            $postCounts[] = $postsByMonthMap[$month] ?? 0;
        }

        return $this->render('admin/blog_dashboard.html.twig', [
            'total_posts'    => $totalPosts,
            'total_comments' => $totalComments,
            'latest_posts'   => $latestPosts,
            'latest_comments' => $latestComments,
            'top_posts'      => $topPostDtos,
            'chart_months'   => json_encode($months),
            'chart_data'     => json_encode($postCounts),
        ]);
    }

    // ── Manage all posts ──
    #[Route('/admin/blog/posts', name: 'admin_blog_posts')]
    public function managePosts(PostRepository $postRepository): Response
    {
        $posts = $postRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/blog_posts.html.twig', ['posts' => $posts]);
    }

    // ── Delete post (admin) ──
    #[Route('/admin/blog/post/{id}/delete', name: 'admin_blog_post_delete', methods: ['POST'])]
    public function deletePost(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('admin_delete_post_' . $post->getId(), $request->request->get('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();
            $this->addFlash('success', 'Post supprimé avec succès !');
        }

        return $this->redirectToRoute('admin_blog_posts');
    }

    // ── Manage all comments ──
    #[Route('/admin/blog/comments', name: 'admin_blog_comments')]
    public function manageComments(CommentRepository $commentRepository): Response
    {
        $comments = $commentRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/blog_comments.html.twig', ['comments' => $comments]);
    }

    // ── Delete comment (admin) ──
    #[Route('/admin/blog/comment/{id}/delete', name: 'admin_blog_comment_delete', methods: ['POST'])]
    public function deleteComment(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('admin_delete_comment_' . $comment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();
            $this->addFlash('success', 'Commentaire supprimé !');
        }

        return $this->redirectToRoute('admin_blog_comments');
    }

    // ── Blog statistics ──
    #[Route('/admin/blog/statistics', name: 'admin_blog_stats')]
    public function statistics(PostRepository $postRepository, CommentRepository $commentRepository): Response
    {
        // Use DTO-hydrated aggregation instead of PHP-side loops over all posts/comments
        $postDtos    = $postRepository->getPostsPerMonth();
        $commentDtos = $commentRepository->getCommentsPerMonth();

        $postsPerMonth    = [];
        $commentsPerMonth = [];
        foreach ($postDtos as $dto)    { $postsPerMonth[$dto->label]    = (int) $dto->total; }
        foreach ($commentDtos as $dto) { $commentsPerMonth[$dto->label] = (int) $dto->total; }

        $months      = [];
        $postData    = [];
        $commentData = [];
        for ($i = 5; $i >= 0; $i--) {
            $key           = (new \DateTime())->modify("-$i months")->format('Y-m');
            $label         = (new \DateTime())->modify("-$i months")->format('M Y');
            $months[]      = $label;
            $postData[]    = $postsPerMonth[$key] ?? 0;
            $commentData[] = $commentsPerMonth[$key] ?? 0;
        }

        // Top active posts with DTO hydration
        $activePosts = $postRepository->getTopPostsByComments(10);

        return $this->render('admin/blog_statistics.html.twig', [
            'posts_per_month'    => $postsPerMonth,
            'comments_per_month' => $commentsPerMonth,
            'months_json'        => json_encode($months),
            'post_data_json'     => json_encode($postData),
            'comment_data_json'  => json_encode($commentData),
            'active_posts'       => $activePosts,
            'total_posts'        => $postRepository->count([]),
            'total_comments'     => $commentRepository->count([]),
        ]);
    }
}
