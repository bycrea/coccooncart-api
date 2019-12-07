<?php

namespace App\Controller;

use App\Entity\UserOptions;
use DateTime;
use Exception;
use App\Entity\Cart;
use App\Entity\Category;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @Route("/cart", name="cart_")
 */
class CartController extends AbstractController
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
     * @Route("/getlist", name="getlist")
     * @param Request $request
     * @return JsonResponse
     */
    public function getListAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $error = false;
        $user = $this->getUserFromToken();
        $options = $this->em->getRepository(UserOptions::class)->findOneBy(['users' => $user]);

        try {
            if($data['orderBy'] != null) {
                $options->setOrderBy($data['orderBy']);
                $this->em->persist($options);
                $this->em->flush();
            }

            $cart = $this->em->getRepository(Cart::class)->lastUserList($user);
            $categories = $this->em->getRepository(Category::class)->AllCategories($options->getOrderBy());

            $listId = !empty($cart) ? $cart->getId() : null;
            $list   = !empty($cart) ? $cart->getList() : null;
            $modify = !empty($cart) ? $cart->getModifyAt() : new DateTime();

            return $this->json([
                'username' => $user->getUsername(),
                'error' => $error,
                'list' => $list,
                'listId' => $listId,
                'modify' => $modify->format('d/m/y H:i'),
                'categories' => $categories,
                'orderBy' => $options->getOrderBy()
            ]);

        } catch (Exception $e) {

            return $this->json([
                'error' => $e,
            ]);
        }
    }


    /**
     * @Route("/updateproduct", name="updateproduct")
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProductAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $cart = new Cart();
        if(!empty($data['listId']))
        {
            $cart = $this->em->getRepository(Cart::class)->find($data['listId']);
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

            $cart->setNbProduct(count($cart->getList()))
                ->setClosed(false)
                ->setAmount(0)
                ->setModifyAt(new DateTime())
                ->setUsers($this->getUserFromToken());

            $this->em->persist($cart);
            $this->em->flush();

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
     * @Route("/closelist", name="closelist")
     * @param Request $request
     * @return JsonResponse
     */
    public function closeListAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $cart = $this->em->getRepository(Cart::class)->find($data['listId']);
        $products = $cart->getList();

        $uncheckedProducts = [];
        $checkedProducts = [];
        foreach ($products as $product) {
            if(!isset($product['checked']) || !$product['checked'] || $product['idcategory'] == 13) {
                $uncheckedProducts[] = $product;
            } else {
                $checkedProducts[] = $product;
            }
        }

        $error = false;
        try {
            $cart->setList($checkedProducts)
                ->setNbProduct(count($checkedProducts))
                ->setClosed(true)
                ->setAmount($data['amount'])
                ->setModifyAt(new DateTime())
                ->setUsers($this->getUserFromToken());

            $this->em->persist($cart);

            if(count($uncheckedProducts)) {
                $newCart = new Cart();
                $newCart->setList($uncheckedProducts)
                    ->setNbProduct(count($uncheckedProducts))
                    ->setClosed(false)
                    ->setAmount(0)
                    ->setModifyAt(new DateTime())
                    ->setUsers($this->getUserFromToken());

                $this->em->persist($newCart);
            }

            $this->em->flush();

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