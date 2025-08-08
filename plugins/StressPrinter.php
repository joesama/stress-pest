<?php

namespace Joesama\StressPest;

use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

trait StressPrinter
{
    public function exportPdf(Collection $result): bool
    {
        $now = Carbon::now();

        $folder = 'stress/'.$now->year.'/';
        $filename = $now->format('Ymd').'.pdf';
        $filePath = $folder.$filename;

        $disk = Storage::disk('local');

        $disk->makeDirectory($folder);

        $output = SnappyPdf::loadView(
            'test.stress',
            [
                'result' => $result,
            ]
        )->setPaper('A4', 'Portrait')
            ->setOption('footer-left', 'Print Date: '.date('d/m/Y'))
            ->setOption('footer-right', 'Page [page] / [topage]')
            ->setOption('footer-font-size', 8)
            ->output();

        $disk->put($filePath, $output);

        return Storage::fileExists($filePath);
    }
}