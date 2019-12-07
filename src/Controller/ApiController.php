<?php

namespace App\Controller;

use App\Entity\User;
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
     * @Route("/getuserdata", name="getuserdata")
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    /*public function getUserDataAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUserFromToken();
        $error = !($data['username'] == $user->getUsername());

        $options = false;
        if(!$error) {
            $options = $this->em->getRepository(UserOptions::class)->findOneBy(['users' => $user]);

            if(empty($options)) {
                $options = new UserOptions($user);
            } else {
                $options->setNbConnection($options->getNbConnection()+1);
                $options->setLastConnection(new DateTime());
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($options);
            $entityManager->flush();

            $options = $this->em->getRepository(UserOptions::class)->getUserOptions($user);
        }

        return $this->json([
            'error' => $error,
            'options' => $options
        ]);
    }*/
}