<?php

namespace App\EvenListener;

use App\Entity\User;
use App\Entity\UserOptions;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

class JWTCreatedListener
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param JWTCreatedEvent $event
     *
     * @return void
     */
    public function addToPayload(JWTCreatedEvent $event)
    {
        $payload = $event->getData();

        $userOptions = $this->updateUserOptions($payload['username']);

        $payload['orderBy'] = $userOptions['orderBy'];
        $payload['nbConnection'] = $userOptions['nbConnection'];
        $payload['lastConnection'] = $userOptions['lastConnection'];
        $event->setData($payload);

        $header = $event->getHeader();
        $header['cty'] = 'JWT';

        $event->setHeader($header);
    }


    private function updateUserOptions($username) {
        $user = $this->em->getRepository(User::class)
            ->findOneBy(['username' => $username]);
        $options = $this->em->getRepository(UserOptions::class)
            ->findOneBy(['users' => $user]);

        $options->setNbConnection($options->getNbConnection()+1);
        $options->setLastConnection(new DateTime());

        $this->em->persist($options);
        $this->em->flush();

        return $this->em->getRepository(UserOptions::class)->getUserOptions($user);
    }
}