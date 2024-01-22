<?php

namespace App\Repository;

use App\Entity\Candidate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Candidate>
 *
 * @method Candidate|null find($id, $lockMode = null, $lockVersion = null)
 * @method Candidate|null findOneBy(array $criteria, array $orderBy = null)
 * @method Candidate[]    findAll()
 * @method Candidate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CandidateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Candidate::class);
    }

    /**
     * @return Candidate[] Returns an array of Candidate objects
     */
    public function findUnread(string $sort, string $sortDirection): array
    {
        return $this->findByViewedField(0, $sort, $sortDirection);
    }

    /**
     * @return Candidate[] Returns an array of Candidate objects
     */
    public function findRead(string $sort, string $sortDirection): array
    {
        return $this->findByViewedField(1, $sort, $sortDirection);
    }

    public function setRead(int $id): void
    {
        $candidate = $this->find($id);
        if (!$candidate) {
            return;
        }
        $this->getEntityManager()->persist($candidate);
        $candidate->setViewed(true);
        $candidate->setUpdatedAt(new \DateTimeImmutable());

        $this->getEntityManager()->flush();
    }

    public function findOneById(int $id): ?Candidate
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function findByViewedField(int $viewed, string $sort, string $sortDirection): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.viewed = :val')
            ->setParameter('val', $viewed)
            ->orderBy('c.' . $sort, $sortDirection)
            ->getQuery()
            ->getResult()
            ;
    }
}
