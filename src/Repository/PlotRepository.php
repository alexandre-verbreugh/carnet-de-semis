<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Plot;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Plot>
 */
class PlotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Plot::class);
    }

    /**
     * Emplacements d'un jardinier, actifs d'abord.
     *
     * @return list<Plot>
     */
    public function findForOwner(User $owner): array
    {
        /** @var list<Plot> $plots */
        $plots = $this->createQueryBuilder('p')
            ->andWhere('p.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('p.isArchived', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $plots;
    }

    /**
     * @return list<Plot>
     */
    public function findActiveForOwner(User $owner): array
    {
        /** @var list<Plot> $plots */
        $plots = $this->createQueryBuilder('p')
            ->andWhere('p.owner = :owner')
            ->andWhere('p.isArchived = false')
            ->setParameter('owner', $owner)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $plots;
    }

    public function countForOwner(User $owner): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.owner = :owner')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
