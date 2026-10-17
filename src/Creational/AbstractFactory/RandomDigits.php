<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\AbstractFactory;

use Random\RandomException;

final class RandomDigits
{
    /**
     * @throws RandomException
     */
    public static function ofLength(int $digitCount): string
    {
        $digits = '';
        for ($digitPosition = 0; $digitPosition < $digitCount; $digitPosition++) {
            $digits .= random_int(0, 9);
        }

        return $digits;
    }
}
