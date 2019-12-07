<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * @param string $orderBy
     * @return Category[] Returns an array of Category objects
     */
    public function AllCategories($orderBy)
    {
        $query = $this->createQueryBuilder('c');

        switch($orderBy) {
            case "a-Z": $query->orderBy('c.name', 'ASC'); break;
            case "Z-a": $query->orderBy('c.name', 'DESC'); break;
            case "Def": $query->orderBy('c.id', 'ASC'); break;
            case "Inv": $query->orderBy('c.id', 'DESC'); break;
            default: $query->orderBy('c.id', 'ASC'); break;
        }
        
        return $query->getQuery()->getArrayResult();
    }
}
