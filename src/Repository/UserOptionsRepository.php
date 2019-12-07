<?php

namespace App\Repository;

use App\Entity\UserOptions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method UserOptions|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserOptions|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserOptions[]    findAll()
 * @method UserOptions[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserOptionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserOptions::class);
    }


    /**
     * @param $user
     * @return mixed
     */
    public function getUserOptions($user) {
        $query = $this->createQueryBuilder('uo')
            ->andWhere('uo.users = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getArrayResult();
        return $query[0];
    }
}
