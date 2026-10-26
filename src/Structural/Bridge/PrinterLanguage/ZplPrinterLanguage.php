<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Bridge\PrinterLanguage;

use DesignPatterns\Structural\Bridge\FontSize;
use DesignPatterns\Structural\Bridge\LabelPrinterLanguage;

final readonly class ZplPrinterLanguage implements LabelPrinterLanguage
{
    #[\Override]
    public function startLabel(): string
    {
        return "^XA\n";
    }

    #[\Override]
    public function text(int $xInDots, int $yInDots, string $content, FontSize $fontSize): string
    {
        $fontHeightInDots = match ($fontSize) {
            FontSize::Normal => 30,
            FontSize::Large => 60,
        };

        return "^FO{$xInDots},{$yInDots}^A0N,{$fontHeightInDots},{$fontHeightInDots}^FD{$content}^FS\n";
    }

    #[\Override]
    public function barcode(int $xInDots, int $yInDots, string $data): string
    {
        return "^FO{$xInDots},{$yInDots}^BCN,100,Y,N,N^FD{$data}^FS\n";
    }

    #[\Override]
    public function box(int $xInDots, int $yInDots, int $widthInDots, int $heightInDots, int $lineThicknessInDots): string
    {
        return "^FO{$xInDots},{$yInDots}^GB{$widthInDots},{$heightInDots},{$lineThicknessInDots}^FS\n";
    }

    #[\Override]
    public function endLabel(): string
    {
        return "^XZ\n";
    }
}
