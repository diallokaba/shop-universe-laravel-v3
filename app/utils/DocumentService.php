<?php

namespace App\utils;

use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;

class DocumentService
{

    public static function generateQrcode($data, $filename){
        $qrCode = Builder::create()
            ->writer(new PngWriter())
            ->data($data)
            ->encoding(new Encoding('UTF-8'))
            ->size(300)
            ->build();

        $filePath = storage_path('app/temp') . '/' . $filename;

        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        file_put_contents($filePath, $qrCode->getString());

        return $filePath;
    }

    public static function generatePdf($view, $data, $filename)
    {
        $pdf = Pdf::loadView($view, $data);

        $filePath = storage_path('app/temp') . '/' . $filename;

        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        $pdf->save($filePath);

        return $filePath;
    }
}