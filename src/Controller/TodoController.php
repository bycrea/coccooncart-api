<?php

namespace App\Controller;

use App\Entity\Todo;
use DateTime;
use Exception;
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
     * @return JsonResponse
     */
    public function getAllAction()
    {
        $error = false;
        $user = $this->getUserFromToken();

        try {
            $todos = $this->em->getRepository(Todo::class)->getTodosByUser($user);
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
     * @Route("/newtodo", name="newtodo")
     * @param Request $request
     * @return JsonResponse
     */
    public function newToDoAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $todo = new Todo();

        $error = false;
        try {
            $todo->setLibelle($data['libelle'])
                ->setList([])
                ->setNbTick(0)
                ->setModifyAt(new DateTime())
                ->setClosed(false)
                ->setUsers($this->getUserFromToken());

            $this->em->persist($todo);
            $this->em->flush();

            $id = $todo->getId();
            $todo = $this->em->getRepository(Todo::class)->getTodoById($id);
            $todoDatetime = $todo['modifyAt'];
            $todo['modifyAt'] = $todoDatetime->format('d/m/y H:i');
            $todo['date'] = $todoDatetime->format('d/m/y');
            $todo['hour'] = $todoDatetime->format('H:i');

        } catch (Exception $e) {

            $error = $e;
        }

        return $this->json([
            'error' => $error,
            'todo' => $todo
        ]);
    }


    /**
     * @Route("/deletetodo", name="deletetodo")
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteTodoAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $error = false;
        try {
            $todo = $this->em->getRepository(Todo::class)->find($data['id']);

            $this->em->remove($todo);
            $this->em->flush();

        } catch (Exception $e) {

            $error = $e;
        }

        return $this->json([
            'error' => $error
        ]);
    }


    /**
     * @Route("/updatelist", name="updatelist")
     * @param Request $request
     * @return JsonResponse
     */
    public function updateList(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if(!empty($id = $data['todoId']))
        {
            $todo = $this->em->getRepository(Todo::class)->find($id);
        } else {
            return $this->json(['error' => true]);
        }

        $error = false;
        try {
            $todo->setList($data['list'])
                ->setNbTick(count($todo->getList()))
                ->setClosed(false)
                ->setModifyAt(new DateTime());

            $this->em->persist($todo);
            $this->em->flush();

            $id = $todo->getId();
            $todo = $this->em->getRepository(Todo::class)->getTodoById($id);
            $todoDatetime = $todo['modifyAt'];
            $todo['modifyAt'] = $todoDatetime->format('d/m/y H:i');
            $todo['date'] = $todoDatetime->format('d/m/y');
            $todo['hour'] = $todoDatetime->format('H:i');

        } catch (Exception $e) {

            $error = $e;
        }

        return $this->json([
            'error' => $error,
            'todo' => $todo,
        ]);
    }


    /**
     * @Route("/closetodo", name="closetodo")
     * @param Request $request
     * @return JsonResponse
     */
    public function closeTodo(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if(!empty($id = $data['todoId']))
        {
            $todo = $this->em->getRepository(Todo::class)->find($id);
        } else {
            return $this->json(['error' => true]);
        }

        $error = false;
        try {
            $todo->setList($data['list'])
                ->setClosed(true)
                ->setModifyAt(new DateTime());

            $this->em->persist($todo);
            $this->em->flush();

            $id = $todo->getId();
            $todo = $this->em->getRepository(Todo::class)->getTodoById($id);
            $todoDatetime = $todo['modifyAt'];
            $todo['modifyAt'] = $todoDatetime->format('d/m/y H:i');
            $todo['date'] = $todoDatetime->format('d/m/y');
            $todo['hour'] = $todoDatetime->format('H:i');

        } catch (Exception $e) {

            $error = $e;
        }

        return $this->json([
            'error' => $error,
            'todo' => $todo,
        ]);
    }

}