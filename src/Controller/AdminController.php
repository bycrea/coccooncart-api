<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @Route("/admin", name="admin_")
 */
class AdminController extends AbstractController
{
    private $jwtManager;
    private $tokenStorageInterface;
    private $em;


    public function __construct(TokenStorageInterface $tokenStorageInterface,
        JWTTokenManagerInterface $jwtManager, EntityManagerInterface $entityManager)
    {
        $this->jwtManager = $jwtManager;
        $this->tokenStorageInterface = $tokenStorageInterface;
        $this->em = $entityManager;
    }


    protected function getUserFromToken()
    {
        $decodedJwtToken = $this->jwtManager->decode($this->tokenStorageInterface->getToken());
        $username = $decodedJwtToken['username'];
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);
        return $user;
    }


    /**
     * @Route("/dash", name="dash")
     */
    public function dashAction()
    {
        return $this->json([
            'error' => false
        ]);
    }


    /**
     * @Route("/refactor_product_catg/{from}/{to}", name="refactor_catg_from_to")
     * @param int $from
     * @param int $to
     * @return JsonResponse
     */
    public function refactorAllProductsCatgFromToAction(int $from, int $to)
    {
        $carts = $this->em->getRepository(Cart::class)->findAll();

        $lists = [];
        foreach ($carts as $cart) {
            $lists[] = $cart->getList();
        }

        foreach ($lists as $list) {
            foreach ($list as $product) {
                if($product['idcategory'] == $from) {
                    $product['idcategory'] = $to;
                }
                dump($product);
            }
        }

        dd('done');

        $error = false;
        try {
            foreach ($carts as $key => $cart) {
                $cart->setList($lists[$key]);
                $this->em->persist($cart);
            }
            $this->em->flush();

        } catch (Exception $e) {
            $error = $e;
        }

        return $this->json([
            'error' => $error,
            'lists' => $lists,
            'done' => 'done'
        ]);
    }
}