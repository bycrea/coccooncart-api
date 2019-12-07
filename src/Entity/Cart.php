<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CartRepository")
 */
class Cart
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="json")
     */
    private $list = [];

    /**
     * @ORM\Column(type="integer")
     */
    private $nbProduct;

    /**
     * @ORM\Column(type="datetime")
     */
    private $ModifyAt;

    /**
     * @ORM\Column(type="boolean")
     */
    private $closed;

    /**
     * @ORM\Column(type="float")
     */
    private $amount;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="carts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $users;


    public function __construct()
    {
        $this->setModifyAt(new DateTime());
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getList(): ?array
    {
        return $this->list;
    }

    public function setList(array $list): self
    {
        $this->list = $list;

        return $this;
    }

    public function getNbProduct(): ?int
    {
        return $this->nbProduct;
    }

    public function setNbProduct(int $nbProduct): self
    {
        $this->nbProduct = $nbProduct;

        return $this;
    }

    public function getModifyAt(): ?\DateTimeInterface
    {
        return $this->ModifyAt;
    }

    public function setModifyAt(\DateTimeInterface $ModifyAt): self
    {
        $this->ModifyAt = $ModifyAt;

        return $this;
    }

    public function getClosed(): ?bool
    {
        return $this->closed;
    }

    public function setClosed(bool $closed): self
    {
        $this->closed = $closed;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

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
