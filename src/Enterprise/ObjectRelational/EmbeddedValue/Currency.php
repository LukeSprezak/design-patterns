<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\EmbeddedValue;

enum Currency: string
{
    case Pln = 'PLN';
    case Eur = 'EUR';
}
