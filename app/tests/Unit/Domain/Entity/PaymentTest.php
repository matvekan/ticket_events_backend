<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Payment;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\ValueObject\PaymentStatus;
use App\Tests\Unit\Domain\Support\DomainFixture;
use App\Tests\Unit\Domain\Support\FixedClock;
use PHPUnit\Framework\TestCase;

final class PaymentTest extends TestCase
{
    public function testPlaceCreatesPendingPaymentWithCorrectAmount(): void
    {
        $payment = $this->createPendingPayment();

        self::assertSame(PaymentStatus::Pending, $payment->status());
        self::assertSame(5000, $payment->amount());
    }

    public function testMarkPaidTransitionsPendingPaymentToPaid(): void
    {
        $clock = DomainFixture::clock();
        $payment = $this->createPendingPayment($clock);

        $payment->markPaid($clock);

        self::assertSame(PaymentStatus::Paid, $payment->status());
    }

    public function testMarkPaidThrowsBusinessRuleViolationWhenPaymentIsAlreadyPaid(): void
    {
        $clock = DomainFixture::clock();
        $payment = $this->createPaidPayment($clock);

        $this->expectException(BusinessRuleViolationException::class);

        $payment->markPaid($clock);
    }

    public function testMarkPaidThrowsBusinessRuleViolationWhenPaymentIsFailed(): void
    {
        $clock = DomainFixture::clock();
        $payment = $this->createFailedPayment($clock);

        $this->expectException(BusinessRuleViolationException::class);

        $payment->markPaid($clock);
    }

    public function testMarkFailedTransitionsPendingPaymentToFailed(): void
    {
        $clock = DomainFixture::clock();
        $payment = $this->createPendingPayment($clock);

        $payment->markFailed($clock);

        self::assertSame(PaymentStatus::Failed, $payment->status());
    }

    public function testMarkFailedThrowsBusinessRuleViolationWhenPaymentIsPaid(): void
    {
        $clock = DomainFixture::clock();
        $payment = $this->createPaidPayment($clock);

        $this->expectException(BusinessRuleViolationException::class);

        $payment->markFailed($clock);
    }

    public function testMarkFailedThrowsBusinessRuleViolationWhenPaymentIsAlreadyFailed(): void
    {
        $clock = DomainFixture::clock();
        $payment = $this->createFailedPayment($clock);

        $this->expectException(BusinessRuleViolationException::class);

        $payment->markFailed($clock);
    }

    public function testRestartTransitionsFailedPaymentToPending(): void
    {
        $payment = $this->createFailedPayment();

        $payment->restart();

        self::assertSame(PaymentStatus::Pending, $payment->status());
    }

    public function testRestartThrowsBusinessRuleViolationWhenPaymentIsPending(): void
    {
        $payment = $this->createPendingPayment();

        $this->expectException(BusinessRuleViolationException::class);

        $payment->restart();
    }

    public function testRestartThrowsBusinessRuleViolationWhenPaymentIsPaid(): void
    {
        $payment = $this->createPaidPayment();

        $this->expectException(BusinessRuleViolationException::class);

        $payment->restart();
    }

    public function testMarkRefundedTransitionsPaidPaymentToRefunded(): void
    {
        $clock = DomainFixture::clock();
        $payment = $this->createPaidPayment($clock);

        $payment->markRefunded($clock);

        self::assertSame(PaymentStatus::Refunded, $payment->status());
    }

    public function testMarkRefundedThrowsBusinessRuleViolationWhenPaymentIsPending(): void
    {
        $clock = DomainFixture::clock();
        $payment = $this->createPendingPayment($clock);

        $this->expectException(BusinessRuleViolationException::class);

        $payment->markRefunded($clock);
    }

    public function testMarkRefundedThrowsBusinessRuleViolationWhenPaymentIsFailed(): void
    {
        $clock = DomainFixture::clock();
        $payment = $this->createFailedPayment($clock);

        $this->expectException(BusinessRuleViolationException::class);

        $payment->markRefunded($clock);
    }

    private function createPendingPayment(?FixedClock $clock = null): Payment
    {
        $clock ??= DomainFixture::clock();
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);
        $order = DomainFixture::orderWithTickets($user, $clock, $ids);

        return Payment::place($order->id(), 5000, $clock, $ids);
    }

    private function createPaidPayment(?FixedClock $clock = null): Payment
    {
        $clock ??= DomainFixture::clock();
        $payment = $this->createPendingPayment($clock);
        $payment->markPaid($clock);

        return $payment;
    }

    private function createFailedPayment(?FixedClock $clock = null): Payment
    {
        $clock ??= DomainFixture::clock();
        $payment = $this->createPendingPayment($clock);
        $payment->markFailed($clock);

        return $payment;
    }
}
