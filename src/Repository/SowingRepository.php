<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Sowing;
use App\Entity\User;
use App\Enum\SowingStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sowing>
 */
class SowingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sowing::class);
    }

    /**
     * @return list<Sowing>
     */
    public function findAllForOwner(User $owner): array
    {
        /** @var list<Sowing> $sowings */
        $sowings = $this->baseQuery($owner)->getQuery()->getResult();

        return $sowings;
    }

    /**
     * Semis encore suivis, du plus recent au plus ancien.
     *
     * @return list<Sowing>
     */
    public function findActiveForOwner(User $owner): array
    {
        /** @var list<Sowing> $sowings */
        $sowings = $this->baseQuery($owner)
            ->andWhere('s.status NOT IN (:closed)')
            ->setParameter('closed', [SowingStatus::Termine, SowingStatus::Echec])
            ->getQuery()
            ->getResult();

        return $sowings;
    }

    /**
     * Semis dont la levee est toujours attendue.
     *
     * Tri croissant : les plus anciens, donc les plus en retard, remontent en
     * tete du tableau de bord.
     *
     * @return list<Sowing>
     */
    public function findAwaitingGerminationForOwner(User $owner): array
    {
        /** @var list<Sowing> $sowings */
        $sowings = $this->createQueryBuilder('s')
            ->addSelect('e', 'p')
            ->join('s.species', 'e')
            ->join('s.plot', 'p')
            ->andWhere('s.owner = :owner')
            ->andWhere('s.status = :seme')
            ->setParameter('owner', $owner)
            ->setParameter('seme', SowingStatus::Seme)
            ->orderBy('s.sownAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $sowings;
    }

    private function baseQuery(User $owner): QueryBuilder
    {
        return $this->createQueryBuilder('s')
            ->addSelect('e', 'p')
            ->join('s.species', 'e')
            ->join('s.plot', 'p')
            ->andWhere('s.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('s.sownAt', 'DESC')
            ->addOrderBy('s.id', 'DESC');
    }
}
