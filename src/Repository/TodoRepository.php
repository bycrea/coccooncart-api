<?php

namespace App\Repository;

use App\Entity\Todo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Todo|null find($id, $lockMode = null, $lockVersion = null)
 * @method Todo|null findOneBy(array $criteria, array $orderBy = null)
 * @method Todo[]    findAll()
 * @method Todo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TodoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Todo::class);
    }


    public function getTodosByUser($user)
    {
        return $this->createQueryBuilder('t')
            ->select('t')
            ->andWhere('t.users = :user')
            ->setParameter('user', $user)
            ->andWhere('t.closed = 0')
            ->orderBy('t.modifyAt', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;
    }

    public function getTodoById($id)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getArrayResult()
        ;
    }
}
