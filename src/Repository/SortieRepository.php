<?php

namespace App\Repository;

use App\Entity\Sortie;
use App\Repository\ParticipantRepository;
use DateTime;
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
        $queryBuilder = $this->createQueryBuilder('s')
        ->join('s.organisateur', 'o')
        ->addSelect('o')
        ->join('s.etat', 'e')
        ->addSelect('e')
        ->leftJoin('s.participants', 'p')
        ->addSelect('p')
        ->andWhere('s.etat != 7' );

        if (!empty($data->mot)) {
            $queryBuilder
                ->andWhere('s.nom LIKE :mot' )
                ->setParameter('mot', "%{$data->mot}%") ;
        }
        if (!empty($data->campus)) {
            if (!empty($data->campus->getId())) {
                $queryBuilder
                    ->andWhere('s.campus = :id')
                    ->setParameter('id', $data->campus->getId());
            }
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
        //cases à cocher
        if ($data->passee) {
            $queryBuilder
                ->andWhere('s.etat = :passee' )
                ->setParameter('passee', 5) ;
        }

         if ($data->inscrit) {
            $queryBuilder
                ->andWhere('p.id = :id')
                ->setParameter('id', $data->user);
        }
        if ($data->nonInscrit) {
            $queryBuilder
                ->andWhere('p.id != :id')
                ->setParameter('id', $data->user);
        }
        if ($data->organisee) {
            $queryBuilder
                ->andWhere('s.organisateur = :id' )
                ->setParameter('id', $data->user) ;
        }
        $queryBuilder
            ->addOrderBy('s.dateHeureDebut');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return Sortie[] Returns an array of Sortie objects
     */
    public function findAllEtatOuverteOuClotureeOuEnCours(): array
    {
        $entityManager = $this->getEntityManager();
        $dql = "SELECT s FROM App\Entity\Sortie s 
                JOIN s.etat e 
                WHERE DATE_ADD(s.dateHeureDebut, s.duree, 'MINUTE') < :date 
                AND (e.libelle = 'Clôturée' OR e.libelle = 'Activité en cours' OR e.libelle = 'Ouverte')";
        $query = $entityManager->createQuery($dql);
        $query->setParameter('date', date_time_set(new DateTime('now'),0,0,0));

        return $query->getResult();
    }

    /**
     * @return Sortie[] Returns an array of Sortie objects
     */
    public function findAllAArchiver(): array
    {
        $entityManager = $this->getEntityManager();
        $dql = "SELECT s FROM App\Entity\Sortie s 
                JOIN s.etat e 
                WHERE DATE_ADD(s.dateHeureDebut, 1, 'MONTH') < :date 
                AND (e.libelle = 'Annulée' OR e.libelle = 'Activité passée')";
        $query = $entityManager->createQuery($dql);
        $query->setParameter('date', date_time_set(new DateTime('now'),0,0,0));

        return $query->getResult();
    }

    /**
     * @return Sortie[] Returns an array of Sortie objects
     */
    public function findAllEtatOuverteOuClotureeouEnCoursDuJour(): array
    {
        $queryBuilder = $this->createQueryBuilder('s')
            ->join('s.etat', 'e')
            ->addSelect('e')
            ->Where("e.libelle = 'Ouverte'")
            ->orWhere("e.libelle = 'Clôturée'")
            ->orWhere("e.libelle = 'Activité en cours'")
            ->andWhere("s.dateHeureDebut >= :date00")
            ->andWhere("s.dateHeureDebut <= :date2359")
            ->setParameter('date00', date_time_set(new DateTime('now'),0,0,0))
            ->setParameter('date2359', date_time_set(new DateTime('now'),23,59,59));
        ;

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
