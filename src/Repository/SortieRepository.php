<?php

namespace App\Repository;

use App\Entity\Sortie;
use App\Repository\ParticipantRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @method Sortie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sortie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sortie[]    findAll()
 * @method Sortie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

     /**
      * @return Sortie[] Returns an array of Sortie objects
      */
    public function findByFilters($data): array
    {
        $queryBuilder = $this->createQueryBuilder('s');
        if (!empty($data->mot)) {
            $queryBuilder
                ->andWhere('s.nom LIKE :mot' )
                ->setParameter('mot', "%{$data->mot}%") ;
        }
        if (!empty($data->campus->getId())) {
            $queryBuilder
                ->andWhere('s.campus = :id' )
                ->setParameter('id', $data->campus->getId()) ;
        }
        if (!empty($data->dateMin)) {
            $queryBuilder
                ->andWhere('s.dateHeureDebut <= :date' )
                ->setParameter('date', $data->dateMin) ;
        }
        if (!empty($data->dateMax)) {
            $queryBuilder
                ->andWhere('s.dateHeureDebut >= :date' )
                ->setParameter('date', $data->dateMax) ;
        }
        if ($data->passee) {
            $queryBuilder
                ->andWhere('s.etat = :passee' )
                ->setParameter('passee', 5) ;
        }
        if (!($data->inscrit)) {
            $queryBuilder
                ->andWhere(' i.participant = :id' )
                ->setParameter('id', $data->user) ;
        }
        if (!($data->nonInscrit)) {
            $queryBuilder
                ->andWhere('s.campus = :val' )
                ->setParameter('val', $data->campus) ;
        }
        if ($data->organisee) {
            $queryBuilder
                ->andWhere('s.organisateur = :id' )
                ->setParameter('id', $data->user ) ;
        }
        $queryBuilder
            ->addOrderBy('s.dateHeureDebut');

        return $queryBuilder->getQuery()->getResult();
    }




    // /**
    //  * @return Sortie[] Returns an array of Sortie objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Sortie
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
