<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Bridge;

interface LabelPrinterLanguage
{
    public function startLabel(): string;

    public function text(int $xInDots, int $yInDots, string $content, FontSize $fontSize): string;

    public function barcode(int $xInDots, int $yInDots, string $data): string;

    public function box(int $xInDots, int $yInDots, int $widthInDots, int $heightInDots, int $lineThicknessInDots): string;

    public function endLabel(): string;
}
