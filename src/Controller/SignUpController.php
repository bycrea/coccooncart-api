<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/signup", name="signup_")
 */
class SignUpController extends AbstractController
{
    private $em;


    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }


    /**
     * @Route("/register", name="register")
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function registerAction(Request $request)
    {
        return $this->json([
            'error' => false,
            'message' => "register"
        ]);
    }


    /**
     * @Route("/checkemail", name="checkemail")
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function checkEmail(Request $request)
    {
        return $this->json([
            'error' => false,
            'message' => "checkEmail"
        ]);
    }


    /**
     * @Route("/checkusername", name="checkusername")
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function checkUsername(Request $request)
    {
        return $this->json([
            'error' => false,
            'message' => "checkUsername"
        ]);
    }
}