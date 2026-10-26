<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Bridge\PrinterLanguage;

use DesignPatterns\Structural\Bridge\FontSize;
use DesignPatterns\Structural\Bridge\LabelPrinterLanguage;

final readonly class EplPrinterLanguage implements LabelPrinterLanguage
{
    #[\Override]
    public function startLabel(): string
    {
        return "N\n";
    }

    #[\Override]
    public function text(int $xInDots, int $yInDots, string $content, FontSize $fontSize): string
    {
        // EPL has no font heights, only built-in fonts 1-5 and a multiplier.
        $fontAndMultipliers = match ($fontSize) {
            FontSize::Normal => '3,1,1',
            FontSize::Large => '4,2,2',
        };

        return "A{$xInDots},{$yInDots},0,{$fontAndMultipliers},N,\"{$content}\"\n";
    }

    #[\Override]
    public function barcode(int $xInDots, int $yInDots, string $data): string
    {
        return "B{$xInDots},{$yInDots},0,1,2,2,100,B,\"{$data}\"\n";
    }

    #[\Override]
    public function box(int $xInDots, int $yInDots, int $widthInDots, int $heightInDots, int $lineThicknessInDots): string
    {
        $xEndInDots = $xInDots + $widthInDots;
        $yEndInDots = $yInDots + $heightInDots;

        return "X{$xInDots},{$yInDots},{$lineThicknessInDots},{$xEndInDots},{$yEndInDots}\n";
    }

    #[\Override]
    public function endLabel(): string
    {
        return "P1\n";
    }
}
