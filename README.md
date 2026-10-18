# design-patterns

Design patterns in PHP 8.5 built on realistic business scenarios. Every pattern ships with PHPUnit tests: `composer test`.

Wzorce projektowe w PHP 8.5 na realnych scenariuszach biznesowych. Każdy wzorzec ma testy PHPUnit: `composer test`.

## Creational

### Factory Method – `src/Creational/FactoryMethod`
Importing price lists from pharmaceutical wholesalers: the process (EAN validation, deduplication) is shared, while each wholesaler delivers a file in a different format. The base class runs the algorithm and a subclass only decides which parser gets created.

Use it when a class has non-trivial logic in which one step is creating an object, and which object depends on the variant. When there is no shared logic, injecting the dependency through the constructor is enough.

### Abstract Factory – `src/Creational/AbstractFactory`
Carrier integration: each carrier is a set of consistent products (tracking number, label, pricing) that must never be mixed between carriers. The shipment service knows only the interfaces and receives the carrier factory through its constructor.

Use it when the client needs a family of objects that must fit together and you expect new families. Avoid it when there is a single product or the family's composition changes often, because every new product means changing every factory.

### Builder – `src/Creational/Builder`
Issuing an e-prescription: patient, prescriber and medications are added step by step, and `build()` enforces rules that span several steps (at most five medications, a permit number for controlled substances, seven-day validity when an antibiotic is present) before producing an immutable `Prescription`.

Use it when an object is assembled in many steps, parts are optional, and invariants depend on the combination of parts. For a value object with a handful of fields, named arguments and a constructor are enough.

### Prototype – `src/Creational/Prototype`
Product variants in a PIM: a fully configured base product (description, material, categories, images, price) is the prototype, and each size/colour variant is a clone that overrides only SKU, size, colour and optionally price. `__clone()` resets the database id so a variant is persisted as a new record instead of overwriting the prototype.

Use it when a new object is mostly a copy of an existing, already configured one and rebuilding it through the constructor would mean repeating a dozen fields. Beware of shallow copies: readonly value objects can be shared between clones, mutable nested objects must be deep-copied in `__clone()`.

## Kreacyjne

### Factory Method – `src/Creational/FactoryMethod`
Import cenników z hurtowni farmaceutycznych: proces (walidacja EAN, deduplikacja) jest wspólny, a każda hurtownia dostarcza plik w innym formacie. Klasa bazowa realizuje algorytm, a podklasa decyduje tylko o tym, jaki parser powstanie.

Stosuj, gdy klasa ma niebanalną logikę, w której jeden z kroków to utworzenie obiektu, a to, jaki obiekt, zależy od wariantu. Gdy nie ma wspólnej logiki, wystarczy wstrzyknąć zależność przez konstruktor.

### Abstract Factory – `src/Creational/AbstractFactory`
Integracja z kurierami: każdy kurier to komplet spójnych produktów (numer śledzenia, etykieta, cennik), których nie wolno mieszać między kurierami. Serwis wysyłkowy zna tylko interfejsy, a fabrykę kuriera dostaje w konstruktorze.

Stosuj, gdy klient potrzebuje rodziny obiektów, które muszą do siebie pasować, i gdy spodziewasz się nowych rodzin. Nie stosuj, gdy produkt jest jeden albo skład rodziny często się zmienia, bo każdy nowy produkt to zmiana wszystkich fabryk.

### Builder – `src/Creational/Builder`
Wystawianie e-recepty: pacjent, lekarz i leki są dodawane krok po kroku, a `build()` egzekwuje reguły obejmujące kilka kroków naraz (maksymalnie pięć leków, numer uprawnienia przy leku kontrolowanym, ważność 7 dni przy antybiotyku), zanim powstanie niemutowalna `Prescription`.

Stosuj, gdy obiekt powstaje w wielu krokach, część danych jest opcjonalna, a poprawność zależy od kombinacji części. Dla value objectu z kilkoma polami wystarczą named arguments i konstruktor.

### Prototype – `src/Creational/Prototype`
Warianty produktu w systemie PIM: w pełni skonfigurowany produkt bazowy (opis, materiał, kategorie, zdjęcia, cena) jest prototypem, a każdy wariant rozmiar/kolor to klon nadpisujący tylko SKU, rozmiar, kolor i opcjonalnie cenę. `__clone()` zeruje id z bazy, żeby wariant zapisał się jako nowy rekord, a nie nadpisał prototypu.

Stosuj, gdy nowy obiekt jest w większości kopią istniejącego, skonfigurowanego obiektu, a budowanie go przez konstruktor oznaczałoby powtórzenie kilkunastu pól. Uważaj na płytką kopię: value objecty `readonly` można dzielić między klonami, mutowalne obiekty zagnieżdżone trzeba kopiować głęboko w `__clone()`.
