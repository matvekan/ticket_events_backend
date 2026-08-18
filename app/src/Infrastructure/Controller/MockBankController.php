<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Payment\ConfirmPaymentCommand;
use App\Application\Command\Payment\FailPaymentCommand;
use App\Domain\Repository\PaymentRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mock-bank', name: 'mock_bank.')]
final class MockBankController
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly CommandBusInterface $commandBus,
        private readonly string $frontendUrl,
    ) {
    }

    #[Route('/{paymentId}', name: 'checkout', methods: ['GET'])]
    public function checkout(string $paymentId): Response
    {
        $payment = $this->payments->findById(\Symfony\Component\Uid\Uuid::fromRfc4122($paymentId));
        if ($payment === null) {
            return $this->html('<h1>Платёж не найден</h1><p><a href="/">На главную</a></p>', 404);
        }

        $order = $payment->order();
        $amount = number_format($payment->amount() / 100, 2, ',', ' ') . ' Br';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Оплата заказа — МокБанк</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #eef1f6; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 12px 40px rgba(30, 41, 59, .15); width: 420px; max-width: 94vw; overflow: hidden; }
        .head { background: #1e3a8a; color: #fff; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; }
        .head .bank { font-weight: 700; font-size: 18px; }
        .head .badge { background: rgba(255,255,255,.15); border-radius: 999px; padding: 4px 12px; font-size: 12px; }
        .body { padding: 24px; }
        .sum { font-size: 32px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
        .order { color: #64748b; font-size: 13px; margin-bottom: 20px; }
        label { display: block; font-size: 12px; font-weight: 600; color: #475569; margin: 12px 0 6px; text-transform: uppercase; letter-spacing: .04em; }
        input { width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 15px; font-family: monospace; }
        input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, .15); }
        .row { display: flex; gap: 12px; }
        .row > div { flex: 1; }
        .hint { font-size: 12px; color: #94a3b8; margin-top: 10px; }
        .actions { display: flex; gap: 10px; margin-top: 24px; }
        button { flex: 1; padding: 13px; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; border: none; }
        .pay { background: #16a34a; color: #fff; }
        .pay:hover { background: #15803d; }
        .decline { background: #f1f5f9; color: #334155; }
        .decline:hover { background: #e2e8f0; }
        .processing { display: none; text-align: center; color: #2563eb; font-size: 14px; margin-top: 14px; }
        .footer { padding: 14px 24px; background: #f8fafc; font-size: 12px; color: #94a3b8; text-align: center; }
        form.submitting button { pointer-events: none; opacity: .6; }
        form.submitting .processing { display: block; }
    </style>
</head>
<body>
    <div class="card">
        <div class="head">
            <span class="bank">МокБанк</span>
            <span class="badge">Демо-платёж</span>
        </div>
        <form class="body" method="post" action="/mock-bank/{$paymentId}/charge" onsubmit="document.querySelector('form').classList.add('submitting')">
            <div class="sum">{$amount}</div>
            <div class="order">Заказ № {$order->id()->toRfc4122()}</div>
            <label for="pan">Номер карты</label>
            <input id="pan" name="pan" value="4242 4242 4242 4242" maxlength="19" autocomplete="cc-number" required>
            <div class="row">
                <div>
                    <label for="expiry">Срок действия</label>
                    <input id="expiry" name="expiry" value="12/30" maxlength="5" placeholder="ММ/ГГ" autocomplete="cc-exp" required>
                </div>
                <div>
                    <label for="cvc">CVC</label>
                    <input id="cvc" name="cvc" value="123" maxlength="3" autocomplete="cc-csc" required>
                </div>
            </div>
            <div class="hint">Демо-режим: любая карта 4242 4242 4242 4242 успешно оплатит заказ. Оплата спишется сразу после нажатия.</div>
            <div class="actions">
                <button class="pay" type="submit">Оплатить {$amount}</button>
            </div>
            <div class="processing">Обработка платежа…</div>
        </form>
        <form class="body" style="padding-top:0" method="post" action="/mock-bank/{$paymentId}/decline">
            <button class="decline" type="submit">Отменить и вернуться</button>
        </form>
        <div class="footer">МокБанк · симуляция платёжного шлюза для демо</div>
    </div>
</body>
</html>
HTML;

        return new Response($html);
    }

    #[Route('/{paymentId}/charge', name: 'charge', methods: ['POST'])]
    public function charge(string $paymentId): Response
    {
        $payment = $this->payments->findById(\Symfony\Component\Uid\Uuid::fromRfc4122($paymentId));
        if ($payment === null) {
            return $this->redirectBack('error');
        }

        sleep(1);

        $this->commandBus->dispatch(new ConfirmPaymentCommand(
            orderId: $payment->order()->id()->toRfc4122(),
        ));

        return $this->redirectBack('success', $payment->order()->id()->toRfc4122());
    }

    #[Route('/{paymentId}/decline', name: 'decline', methods: ['POST'])]
    public function decline(string $paymentId): Response
    {
        $this->commandBus->dispatch(new FailPaymentCommand($paymentId));

        return $this->redirectBack('failed');
    }

    private function redirectBack(string $result, ?string $orderId = null): RedirectResponse
    {
        $target = $orderId !== null
            ? sprintf('%s/orders/%s?payment=%s', $this->frontendUrl, $orderId, $result)
            : sprintf('%s?payment=%s', $this->frontendUrl, $result);

        return new RedirectResponse($target);
    }

    private function html(string $content, int $status = 200): Response
    {
        return new Response(sprintf('<html><body style="font-family:sans-serif;padding:40px">%s</body></html>', $content), $status);
    }
}
