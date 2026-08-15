<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Plot;
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
     * Emplacements encore en service, du plus recemment rempli au plus ancien.
     *
     * @return list<Plot>
     */
    public function findActive(): array
    {
        /** @var list<Plot> $plots */
        $plots = $this->createQueryBuilder('p')
            ->andWhere('p.isArchived = false')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $plots;
    }
}
