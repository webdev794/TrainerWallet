<?php

namespace App\Services;

use App\Models\Invoice;
use App\Services\Payments\ManualUpiGateway;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class UpiQrService
{
    public function intentUri(Invoice $invoice): ?string
    {
        $profile = $invoice->trainer->trainerProfile;

        if ($profile === null || $profile->upi_vpa === null || $profile->upi_vpa === '') {
            return null;
        }

        return ManualUpiGateway::buildIntentUri(
            $profile->upi_vpa,
            $profile->business_name,
            $invoice->outstanding(),
            $invoice->number,
        );
    }

    /**
     * Return a data: URI PNG of the UPI QR, or null when no VPA is set.
     */
    public function dataUri(Invoice $invoice): ?string
    {
        $intent = $this->intentUri($invoice);

        if ($intent === null) {
            return null;
        }

        $result = (new Builder(
            writer: new PngWriter,
            data: $intent,
            size: 280,
            margin: 8,
        ))->build();

        return $result->getDataUri();
    }
}
