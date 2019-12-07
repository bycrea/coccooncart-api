<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserOptionsRepository")
 */
class UserOptions
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $orderBy;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $nbConnection;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastConnection;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\User", inversedBy="options", cascade={"persist", "remove"})
     */
    private $users;


    public function __construct($user)
    {
        $this->setOrderBy('Def');
        $this->setNbConnection(1);
        $this->setLastConnection(new DateTime());
        $this->setUsers($user);
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderBy(): ?string
    {
        return $this->orderBy;
    }

    public function setOrderBy(?string $orderBy): self
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    public function getNbConnection(): ?int
    {
        return $this->nbConnection;
    }

    public function setNbConnection(?int $nbConnection): self
    {
        $this->nbConnection = $nbConnection;

        return $this;
    }

    public function getLastConnection(): ?\DateTimeInterface
    {
        return $this->lastConnection;
    }

    public function setLastConnection(?\DateTimeInterface $lastConnection): self
    {
        $this->lastConnection = $lastConnection;

        return $this;
    }

    public function getUsers(): ?User
    {
        return $this->users;
    }

    public function setUsers(?User $users): self
    {
        $this->users = $users;

        return $this;
    }
}
