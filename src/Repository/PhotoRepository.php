<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Photo;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Photo>
 */
class PhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Photo::class);
    }

    /**
     * Une photo n'a pas de proprietaire propre : elle suit l'observation a
     * laquelle elle est rattachee.
     *
     * @return list<Photo>
     */
    public function findForOwner(User $owner): array
    {
        /** @var list<Photo> $photos */
        $photos = $this->createQueryBuilder('p')
            ->join('p.observation', 'o')
            ->andWhere('o.owner = :owner')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getResult();

        return $photos;
    }

    public function countForOwner(User $owner): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->join('p.observation', 'o')
            ->andWhere('o.owner = :owner')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
