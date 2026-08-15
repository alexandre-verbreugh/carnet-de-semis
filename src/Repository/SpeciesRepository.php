<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Species;
use App\Entity\User;
use App\Enum\SpeciesCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Species>
 */
class SpeciesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Species::class);
    }

    /**
     * Catalogue visible par un jardinier : les fiches livrees avec le projet,
     * plus ses propres varietes.
     *
     * Le filtre sur le mois de semis est applique en PHP : les mois sont
     * stockes en JSON, et le catalogue tient dans quelques dizaines de lignes.
     * Une requete SQL portable sur du JSON couterait bien plus cher a maintenir
     * que ce qu'elle ferait gagner.
     *
     * @return list<Species>
     */
    public function search(User $owner, ?string $terme = null, ?SpeciesCategory $categorie = null, ?int $mois = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.owner IS NULL OR e.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('e.name', 'ASC')
            ->addOrderBy('e.variety', 'ASC');

        if (null !== $terme && '' !== trim($terme)) {
            $qb->andWhere('LOWER(e.name) LIKE :terme OR LOWER(e.variety) LIKE :terme OR LOWER(e.family) LIKE :terme')
                ->setParameter('terme', '%'.mb_strtolower(trim($terme)).'%');
        }

        if (null !== $categorie) {
            $qb->andWhere('e.category = :categorie')->setParameter('categorie', $categorie);
        }

        /** @var list<Species> $especes */
        $especes = $qb->getQuery()->getResult();

        if (null !== $mois) {
            $especes = array_values(array_filter(
                $especes,
                static fn (Species $espece): bool => $espece->isSowableInMonth($mois),
            ));
        }

        return $especes;
    }

    public function countVisibleFor(User $owner): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.owner IS NULL OR e.owner = :owner')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
