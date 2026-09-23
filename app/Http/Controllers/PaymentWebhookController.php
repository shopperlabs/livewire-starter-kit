<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Shopper\Payment\Actions\IngestPaymentEvent;
use Shopper\Payment\Facades\Payment;
use Throwable;

final class PaymentWebhookController
{
    public function __construct(
        private readonly IngestPaymentEvent $ingest,
    ) {}

    public function __invoke(Request $request, string $driver): JsonResponse
    {
        if (! in_array($driver, Payment::availableDrivers(), strict: true) || ! Payment::isConfigured($driver)) {
            abort(404);
        }

        try {
            $result = Payment::driver($driver)->handleWebhook(
                [...$request->all(), '_raw_body' => $request->getContent()],
                array_map(
                    static fn (array $values): string => (string) ($values[0] ?? ''),
                    $request->headers->all(),
                ),
            );
        } catch (Throwable) {
            return response()->json(['error' => 'Invalid webhook payload.'], 400);
        }

        $this->ingest->execute($driver, $result);

        return response()->json(['received' => true]);
    }
}
