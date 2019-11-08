<?php

namespace App\Controller;

use App\Entity\Todo;
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
 * @Route("/todo", name="todo_")
 */
class TodoController extends AbstractController
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
     * @Route("/getall", name="getall")
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function todoGetAllAction(EntityManagerInterface $em)
    {
        $error = false;
        $user = $this->getUserFromToken();

        try {
            $todos = $em->getRepository(Todo::class)->getTodosByUser($user);
            foreach ($todos as $key => $todo) {
                $todos[$key]['modifyAt'] = $todo['modifyAt']->format('d/m/y H:i');
                $todos[$key]['date'] = $todo['modifyAt']->format('d/m/y');
                $todos[$key]['hour'] = $todo['modifyAt']->format('H:i');
            }

        } catch (Exception $e) {

            $error = $e;
        }

        return $this->json([
            'username' => $user->getUsername(),
            'error' => $error,
            'todos' => $todos
        ]);
    }


    /**
     * @Route("/getbyid", name="getbyid")
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function todoGetByIdAction(EntityManagerInterface $em, Request $request)
    {
        $error = false;
        $data = json_decode($request->getContent(), true);

        try {
            $todo = $em->getRepository(Todo::class)->getTodoById($data['id']);
            $todo[0]['modifyAt'] = $todo[0]['modifyAt']->format('d/m/y H:i');

        } catch (Exception $e) {

            $error = $e;
        }

        return $this->json([
            'error' => $error,
            'todo' => $todo
        ]);
    }


    /**
     * @Route("/updateProduct", name="updateProduct")
     * @param EntityManagerInterface $em
     * @param Request $request
     * @return JsonResponse
     */
    public function todoUpdateProductAction(EntityManagerInterface $em, Request $request)
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
     * @Route("/closelist", name="closelist")
     * @param EntityManagerInterface $em
     * @param Request $request
     * @return JsonResponse
     */
    public function todoCloseListAction(EntityManagerInterface $em, Request $request)
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