# Design patterns in PHP

Repozytorium do nauki wzorców projektowych. Każdy wzorzec to realny scenariusz biznesowy
(logistyka, e-commerce, farmacja, medycyna, aplikacje biznesowe), nie zabawkowe `Shape`/`Animal`.

## Struktura

- `src/Creational/<Pattern>/` – namespace `DesignPatterns\Creational\<Pattern>`
- `tests/Creational/<Pattern>/` – namespace `DesignPatterns\Tests\Creational\<Pattern>`
- `composer test` uruchamia PHPUnit 12

## Workflow

1. Zaproponuj jeden konkretny scenariusz i uzasadnij, dlaczego ten wzorzec tu pasuje.
   Powiedz też, kiedy ten wzorzec NIE jest właściwym wyborem (np. wystarczy zwykłe DI).
2. Po akceptacji: TDD – najpierw padający test, potem implementacja, potem `composer test`.
3. Rozmowa po polsku, kod w całości po angielsku.

## Konwencje kodu

### Język i nazwy
- Wszystko w kodzie po angielsku: identyfikatory, komunikaty, dane testowe, pola formatów plików.
  Nazwy własne firm (Neuca, Farmacol) zostają.
- Pełne, opisowe nazwy. Nie skracaj: `$importResult` nie `$result`, `$priceListEntry` nie `$entry`,
  `$digitPosition` nie `$i`, `createPriceListParser()` nie `createParser()`.
- Nazwa ma mówić, co przechowuje lub robi, bez zgadywania i bez czytania implementacji.

### Nowoczesny PHP (do 8.5)
Używaj wszystkiego, co język oferuje, gdy upraszcza kod:
- `declare(strict_types=1)` w każdym pliku, `final` domyślnie, `readonly class` dla value objectów
- constructor property promotion, named arguments, `match`, enums (backed, z metodami)
- atrybuty: `#[\Override]` na każdym nadpisaniu, `#[\NoDiscard]` gdy ignorowanie wyniku to błąd,
  `#[\Deprecated]`, `#[\SensitiveParameter]`; w PHPUnit `#[Test]`, `#[CoversClass]`, `#[DataProvider]`
- first-class callable syntax `$this->method(...)`, `static fn` gdy closure nie używa `$this`
- `new X()->method()` bez nawiasów, property hooks, asymmetric visibility (`public private(set)`)
- pipe operator `|>` tylko tam, gdzie kroki są jednoargumentowe i czyta się lepiej niż zmienne pośrednie
- `array_find`, `array_any`, `array_all`, `array_first`, `array_last` zamiast ręcznych pętli
- typy: `never`, DNF, standalone `true`/`false`/`null`; żadnych `mixed` bez powodu
- docblock tylko tam, gdzie typ natywny nie wystarcza (`list<PriceListEntry>`)

### Zasady
- **SOLID**: jedna odpowiedzialność na klasę, otwarte na rozszerzenie przez nową klasę, nie przez `if`,
  zależności od interfejsów, interfejsy małe.
- **DRY**: wspólna logika w jednym miejscu, ale nie abstrakcja dla jednego użycia.
- **KISS**: najprostsze rozwiązanie, które przechodzi testy. Bez warstw „na przyszłość”.
- Prostota i czytelność przed sprytem. Wydajność: bez zbędnych kopii tablic i podwójnych przebiegów,
  ale nie kosztem czytelności, jeśli nie ma dowodu, że to wąskie gardło.
- Pieniądze jako `int` w najmniejszej jednostce (`$netPriceInCents`), nigdy `float`.
- Zakładaj poprawne wejście wewnątrz systemu. Waliduj tylko dane z zewnątrz (plik, sieć, użytkownik).
- Bez try/catch bez konkretnego planu obsługi, bez fallbacków maskujących błędy.

### Testy
- PHPUnit 12 z atrybutami, metody bez prefiksu `test`, nazwy opisujące zachowanie.
- Testuj zachowanie na prawdziwych klasach; mocki tylko gdy nie da się inaczej.
- `#[DataProvider]` z `yield 'opis' => [...]` do przepuszczania tych samych reguł przez różne implementacje.

## README
Po dodaniu każdego wzorca dopisz do `README.md` wpis w obu sekcjach: angielskiej (`## Creational`) nad polską
(`## Kreacyjne`). Wpis to nagłówek z nazwą wzorca i ścieżką, zdanie o scenariuszu z repo
oraz zdanie o tym, kiedy wzorzec stosować, a kiedy nie.
Wzorce PoEAA idą do sekcji `## Enterprise (PoEAA)` / `## Korporacyjne (PoEAA)` z podsekcją `###` per grupa Fowlera
i nagłówkiem `####` per wzorzec. Nowy katalog testów wymaga wpisu `<testsuite>` w `phpunit.xml`.

## Grupowanie plików
Gdy katalog wzorca przekracza ok. 5 plików, grupuj je w podkatalogi z własnym namespace: po rodzinie
(`InPost/`, `Neuca/`) albo po agregacie / pojęciu domenowym (`Prescription/`, `Sale/`). Abstrakcje, klient
i wyjątki zostają w korzeniu wzorca. Testy lustrzanie.
Implementacje testowe (fake'i in-memory, spy) trzymaj w `tests/.../<Pattern>/Fake/`, nie w `src`.
