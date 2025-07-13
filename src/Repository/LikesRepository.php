<?php

namespace App\Repository;

use App\Entity\Likes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Likes>
 */
class LikesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Likes::class);
    }

    /**
    * For given item_id returns the number of users that liked this item.
    */
    public function countLikesForItem(int $item_id): int
    {
        return $this->createQueryBuilder('l')
            ->select('COUNT(l)')
            ->andWhere('l.likedItem = :val')
            ->setParameter('val', $item_id)
            ->getQuery()
            ->getSingleScalarResult()
            ;
    }

    /** Check user likes certain item
     * @return int 0 if user doesn't like this item or 1 if he/she likes it.
     */
    public function checkIsLiked(int $user_id, int $item_id): int {
        return $this->createQueryBuilder('l')
            ->select('COUNT(l)')
            ->andWhere('l.whoLikes = :uval')
            ->setParameter('uval', $user_id)
            ->andWhere('l.likedItem = :ival')
            ->setParameter('ival', $item_id)
            ->getQuery()
            ->getSingleScalarResult()
            ;
    }

    public function findLike(int $user_id, int $item_id): ?Likes
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.whoLikes = :uval')
            ->setParameter('uval', $user_id)
            ->andWhere('l.likedItem = :ival')
            ->setParameter('ival', $item_id)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    //    /**
    //     * @return Likes[] Returns an array of Likes objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('l.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Likes
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
