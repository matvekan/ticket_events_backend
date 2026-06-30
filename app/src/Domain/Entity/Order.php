<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\OrderStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
class Order extends AbstractEntity
{
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'integer')]
    private int $totalAmount;

    #[ORM\Column(length: 3)]
    private string $totalCurrency;

    #[ORM\Column(type: 'string', enumType: OrderStatus::class, length: 20)]
    private OrderStatus $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /** @var Collection<int, Ticket> */
    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'order', cascade: ['persist'])]
    private Collection $tickets;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->status = OrderStatus::Pending;
        $this->createdAt = new \DateTimeImmutable();
        $this->tickets = new ArrayCollection();
        $this->totalAmount = 0;
        $this->totalCurrency = 'RUB';
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTotalAmount(): int
    {
        return $this->totalAmount;
    }

    public function getTotalCurrency(): string
    {
        return $this->totalCurrency;
    }

    public function getTotalAsFloat(): float
    {
        return $this->totalAmount / 100;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, Ticket> */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Ticket $ticket): void
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets->add($ticket);
            $this->totalAmount += $ticket->getPriceAmount();
        }
    }

    public function pay(): void
    {
        if ($this->status !== OrderStatus::Pending) {
            throw new \DomainException('Only pending orders can be paid.');
        }

        $this->status = OrderStatus::Paid;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function cancel(): void
    {
        if ($this->status !== OrderStatus::Pending) {
            throw new \DomainException('Only pending orders can be cancelled.');
        }

        $this->status = OrderStatus::Cancelled;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function refund(): void
    {
        if ($this->status !== OrderStatus::Paid) {
            throw new \DomainException('Only paid orders can be refunded.');
        }

        $this->status = OrderStatus::Refunded;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
