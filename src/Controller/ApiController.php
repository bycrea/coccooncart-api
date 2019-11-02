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
            $modify = !empty($cart)
                ? $cart->getModifyAt()
                : new DateTime();

            return $this->json([
                'username' => $user->getUsername(),
                'error' => $error,
                'list' => $list,
                'listId' => $listId,
                'modify' => $modify->format('d/m/y H:i'),
                'categories' => $categories
            ]);

        } catch (Exception $e) {

            return $this->json([
                'error' => $e,
            ]);
        }
    }


    /**
     * @Route("/updateProduct", name="api_updateProduct")
     * @param EntityManagerInterface $em
     * @param Request $request
     * @return JsonResponse
     */
    public function apiUpdateProductAction(EntityManagerInterface $em, Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $cart = new Cart();
        if(!empty($data['listId']))
        {
            $cart = $em->getRepository(Cart::class)->find($data['listId']);
        }

        $error = false;
        try {

            $list = $cart->getList();

            switch($data['action']) {
                case 'add':
                    array_unshift($list, $data['product']);
                    $cart->setList($list);
                    break;

                case 'check':
                    $product = $list[$data['product']];
                    $product['checked'] = isset($product['checked']) ? !$product['checked'] : true;
                    $list[$data['product']] = $product;
                    $cart->setList($list);
                    break;

                case 'delete':
                    unset($list[$data['product']]);
                    $newList = array_values($list);
                    $cart->setList($newList);
                    break;

                default:
                    $cart->setList($list);
                    break;
            }

            $cart->setNbProduct(count($cart->getList()));
            $cart->setClosed(false);
            $cart->setAmount(0);
            $cart->setModifyAt(new DateTime());
            $cart->setUsers($this->getUserFromToken());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($cart);
            $entityManager->flush();

        } catch (Exception $e) {

            $error = $e;
        }

        return $this->json([
            'error' => $error,
            'list' => $cart->getList(),
            'listId' => $cart->getId(),
            'modify' => $cart->getModifyAt()->format('d/m/y H:i')
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
            $cart->setClosed(true);
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

            $newListId = isset($newCart) ? $newCart->getId() : null;

        } catch (Exception $e) {

            $error = $e;
            $newListId = null;
        }

        return $this->json([
            'error' => $error,
            'newListId' => $newListId,
        ]);
    }

}