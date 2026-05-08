<?php

namespace App\Repository;

use App\Entity\Post;
use App\Dto\StatsDto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * Find posts with optional search and sort.
     * - Author is eagerly loaded via fetch-join (ManyToOne = safe with setMaxResults)
     * - Comments are NOT fetch-joined (OneToMany collection) to avoid row multiplication
     *   Instead, comment counts come from a separate aggregation query.
     *
     * @return array<Post>
     */
    public function findBySearchAndSort(?string $search = null, string $sort = 'latest'): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'a')->addSelect('a');  // ManyToOne: safe

        if ($search && !empty(trim($search))) {
            $qb->andWhere('p.title LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        switch ($sort) {
            case 'most_commented':
                // Sub-select for comment count avoids collection join + setMaxResults conflict
                $qb->addSelect('(SELECT COUNT(c.id) FROM App\Entity\Comment c WHERE c.post = p) AS HIDDEN comment_count')
                   ->orderBy('comment_count', 'DESC');
                break;
            case 'oldest':
                $qb->orderBy('p.createdAt', 'ASC');
                break;
            case 'latest':
            default:
                $qb->orderBy('p.createdAt', 'DESC');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns a map of post ID => comment count for given posts.
     * Uses DTO hydration for performance (3-5x faster than array hydration).
     *
     * @param  array<int> $postIds
     * @return array<int, int> [postId => count]
     */
    public function getCommentCountsByPostIds(array $postIds): array
    {
        if (empty($postIds)) {
            return [];
        }

        /** @var StatsDto[] $rows */
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('NEW App\Dto\StatsDto(CAST(p.id AS string), COUNT(c.id))')
            ->from(Post::class, 'p')
            ->leftJoin('p.comments', 'c')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $postIds)
            ->groupBy('p.id')
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($rows as $dto) {
            $map[(int) $dto->label] = (int) $dto->total;
        }
        return $map;
    }

    /**
     * Top posts by comment count — uses DTO hydration with aggregation.
     * @return StatsDto[]
     */
    public function getTopPostsByComments(int $limit = 5): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('NEW App\Dto\StatsDto(p.title, COUNT(c.id))')
            ->from(Post::class, 'p')
            ->leftJoin('p.comments', 'c')
            ->groupBy('p.id', 'p.title')
            ->orderBy('COUNT(c.id)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count posts per month using DTO hydration.
     * @return StatsDto[]
     */
    public function getPostsPerMonth(): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('NEW App\Dto\StatsDto(SUBSTRING(p.createdAt, 1, 7), COUNT(p.id))')
            ->addSelect('SUBSTRING(p.createdAt, 1, 7) AS HIDDEN month')
            ->from(Post::class, 'p')
            ->where('p.createdAt IS NOT NULL')
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->setMaxResults(12)
            ->getQuery()
            ->getResult();
    }
}

