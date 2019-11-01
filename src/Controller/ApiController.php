<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Category;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


/**
 * @Route("/api", name="api_")
 */
class ApiController extends AbstractController
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
     * @Route("/getlist", name="api_getlist")
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function apiGetListAction(EntityManagerInterface $em)
    {
        $error = false;
        $user = $this->getUserFromToken();

        try {
            $cart = $em->getRepository(Cart::class)->lastUserList($user);
            $categories = $em->getRepository(Category::class)->AllCategories();

            $listId = !empty($cart) ? $cart->getId() : null;
            $list = !empty($cart) ? $cart->getList() : null;

            return $this->json([
                'username' => $user->getUsername(),
                'error' => $error,
                'listId' => $listId,
                'list' => $list,
                'categories' => $categories
            ]);

        } catch (Exception $e) {

            return $this->json([
                'error' => $e,
            ]);
        }
    }


    /**
     * @Route("/updatelist", name="api_updatelist")
     * @param EntityManagerInterface $em
     * @param Request $request
     * @return JsonResponse
     */
    public function apiUpdateListAction(EntityManagerInterface $em, Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $cart = new Cart();
        if(!empty($data['listId']))
        {
            $cart = $em->getRepository(Cart::class)->find($data['listId']);
        }

        $error = false;
        try {
            $cart->setList($data['list']);
            $cart->setNbProduct(count($data['list']));
            $cart->setClosed($data['closed']);
            $cart->setAmount($data['amount']);
            $cart->setModifyAt(new DateTime());
            $cart->setUsers($this->getUserFromToken());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($cart);
            $entityManager->flush();

            $listId = $cart->getId();

        } catch (Exception $e) {

            $error = $e;
            $listId = null;
        }

        return $this->json([
            'error' => $error,
            'listId' => $listId,
            'modify' => $cart->getModifyAt()->format('d/m/y H:i'),
            'nbProduct' => $cart->getNbProduct()
        ]);
    }


    /**
     * @Route("/closelist", name="api_closelist")
     * @param EntityManagerInterface $em
     * @param Request $request
     * @return JsonResponse
     */
    public function apiCloseListAction(EntityManagerInterface $em, Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $cart = $em->getRepository(Cart::class)->find($data['listId']);
        $products = $cart->getList();

        $uncheckedProducts = [];
        $checkedProducts = [];
        foreach ($products as $product) {
            if(!isset($product['checked']) || !$product['checked']) {
                $uncheckedProducts[] = $product;
            } else {
                $checkedProducts[] = $product;
            }
        }

        $error = false;
        try {
            $entityManager = $this->getDoctrine()->getManager();

            $cart->setList($checkedProducts);
            $cart->setNbProduct(count($checkedProducts));
            $cart->setClosed($data['closed']);
            $cart->setAmount($data['amount']);
            $cart->setModifyAt(new DateTime());
            $cart->setUsers($this->getUserFromToken());

            $entityManager->persist($cart);

            if(count($uncheckedProducts)) {
                $newCart = new Cart();
                $newCart->setList($uncheckedProducts);
                $newCart->setNbProduct(count($uncheckedProducts));
                $newCart->setClosed(false);
                $newCart->setAmount(0);
                $newCart->setModifyAt(new DateTime());
                $newCart->setUsers($this->getUserFromToken());

                $entityManager->persist($newCart);
            }

            $entityManager->flush();

            $listId = $cart->getId();
            $newListId = isset($newCart) ? $newCart->getId() : null;

        } catch (Exception $e) {

            $error = $e;
            $listId = null;
            $newListId = null;
        }

        return $this->json([
            'error' => $error,
            'oldId' => $listId,
            'newId' => $newListId,
            'modify' => $cart->getModifyAt()->format('d/m/y H:i'),
            'nbProduct' => $cart->getNbProduct()
        ]);
    }

}