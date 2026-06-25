<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Services\BillingService;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Response;

class GuestMenuQrCodeController extends Controller
{
    public function __invoke(Shop $shop, BillingService $billing): Response
    {
        abort_if($shop->status === 'suspended' || ! $billing->isSubscribed($shop), 404);

        $target = route('guest.menu', $shop);
        $result = (new Builder(
            writer: new SvgWriter,
            writerOptions: [
                SvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION => true,
            ],
            validateResult: false,
            data: $target,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 600,
            margin: 24,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 66, 37),
            backgroundColor: new Color(255, 255, 255),
        ))->build();

        return response($result->getString(), 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
            'X-Bite-QR-Target' => $target,
        ]);
    }
}
