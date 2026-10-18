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

### Singleton – `src/Creational/Singleton`
Fiscal printer connection in a pharmacy: the printer sits on one serial port and keeps continuous receipt numbering, so a second connection in the same process would corrupt it. The class itself enforces a single instance through `getInstance()`, a private constructor, a private `__clone()` and an `__unserialize()` that throws.

Use it only when "exactly one per process" is a hardware or protocol constraint that must hold regardless of configuration. In every other case register a plain class as a shared service in the DI container: it gives one instance too, but stays testable, replaceable and free of global state.

## Enterprise (PoEAA)

Patterns from Martin Fowler's *Patterns of Enterprise Application Architecture*, grouped the way the book groups them.

### Domain Logic

#### Transaction Script – `src/Enterprise/DomainLogic/TransactionScript`
Pharmacy sales as procedures on a PDO connection: `dispensePrescription()` loads the prescription row, checks expiry and prior dispensing, reduces stock, charges the patient by refund level, records the sale and commits, rolling back on any failure; `sellOverTheCounter()` does the simpler counterpart. Logic is procedural, data stays as rows, and there are no domain objects.

Use it for simple domain logic with few rules, where one procedure per request stays readable, or for small systems and prototypes. As rules grow, scripts start duplicating each other (stock checks and sale recording already appear twice here) and that is the moment to move to a Domain Model.

#### Domain Model – `src/Enterprise/DomainLogic/DomainModel`
The same pharmacy sale as objects with behaviour: `RefundLevel` knows how to price a package, `Medication` guards and reduces its own stock, `Prescription::dispense()` checks expiry and prior dispensing and produces a `Sale`, and `Sale::overTheCounter()` reuses the very same `Medication::dispense()`. Each rule lives in exactly one place; the duplication of the Transaction Script is gone. Loading and saving these objects is deliberately absent, that is the job of the data source patterns.

Use it when business rules are many, interlinked and change often, and the cost of designing objects pays back in every new feature. For a thin CRUD application with a handful of rules it is heavier than a Transaction Script.

#### Service Layer – `src/Enterprise/DomainLogic/ServiceLayer`
`PharmacyService` is the application's boundary on top of the Domain Model: one method per use case (`dispensePrescription()`, `sellOverTheCounter()`), each of which opens a transaction, loads objects through repository interfaces, calls a single domain method, saves what changed and returns a plain result for the presentation layer. It contains no business rules itself, only coordination.

Use it when several clients (web, API, CLI, queue consumer) need the same use cases, or when a use case has to coordinate transactions, persistence and notifications around domain logic. Skip it when there is one client and the Domain Model can be called directly from the controller.

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

### Singleton – `src/Creational/Singleton`
Połączenie z drukarką fiskalną w aptece: drukarka wisi na jednym porcie szeregowym i prowadzi ciągłą numerację paragonów, więc drugie połączenie w tym samym procesie by ją zepsuło. Klasa sama wymusza jedną instancję przez `getInstance()`, prywatny konstruktor, prywatne `__clone()` i `__unserialize()` rzucające wyjątek.

Stosuj tylko wtedy, gdy „dokładnie jedna na proces” wynika ze sprzętu lub protokołu i musi obowiązywać niezależnie od konfiguracji. W każdym innym przypadku zarejestruj zwykłą klasę jako shared service w kontenerze DI: też daje jedną instancję, ale pozostaje testowalna, podmienialna i wolna od globalnego stanu.

## Korporacyjne (PoEAA)

Wzorce z książki Martina Fowlera *Patterns of Enterprise Application Architecture*, pogrupowane tak jak w książce.

### Logika domenowa

#### Transaction Script – `src/Enterprise/DomainLogic/TransactionScript`
Sprzedaż w aptece jako procedury na połączeniu PDO: `dispensePrescription()` wczytuje wiersz recepty, sprawdza ważność i wcześniejszą realizację, zdejmuje ze stanu, nalicza dopłatę pacjenta po poziomie refundacji, zapisuje sprzedaż i robi commit, a przy każdym błędzie rollback; `sellOverTheCounter()` to prostszy odpowiednik. Logika jest proceduralna, dane zostają wierszami, obiektów domenowych nie ma.

Stosuj przy prostej logice z niewieloma regułami, gdzie jedna procedura na żądanie pozostaje czytelna, oraz w małych systemach i prototypach. Gdy reguł przybywa, skrypty zaczynają się powielać (sprawdzanie stanu i zapis sprzedaży już tu występują dwa razy) i to jest moment na przejście do Domain Model.

#### Domain Model – `src/Enterprise/DomainLogic/DomainModel`
Ta sama sprzedaż apteczna jako obiekty z zachowaniem: `RefundLevel` wie, jak wycenić opakowanie, `Medication` pilnuje i zdejmuje własny stan, `Prescription::dispense()` sprawdza ważność i wcześniejszą realizację i tworzy `Sale`, a `Sale::overTheCounter()` używa tego samego `Medication::dispense()`. Każda reguła jest w dokładnie jednym miejscu; duplikacja z Transaction Script zniknęła. Wczytywania i zapisu tych obiektów celowo nie ma, to zadanie wzorców źródła danych.

Stosuj, gdy reguł biznesowych jest dużo, są powiązane i często się zmieniają, a koszt zaprojektowania obiektów zwraca się przy każdej nowej funkcji. Dla cienkiej aplikacji CRUD z garstką reguł jest cięższy niż Transaction Script.

#### Service Layer – `src/Enterprise/DomainLogic/ServiceLayer`
`PharmacyService` to granica aplikacji nad Domain Model: jedna metoda na przypadek użycia (`dispensePrescription()`, `sellOverTheCounter()`), z których każda otwiera transakcję, wczytuje obiekty przez interfejsy repozytoriów, woła jedną metodę domenową, zapisuje to, co się zmieniło, i zwraca prosty wynik dla warstwy prezentacji. Sam nie zawiera reguł biznesowych, tylko koordynację.

Stosuj, gdy kilku klientów (web, API, CLI, konsument kolejki) potrzebuje tych samych przypadków użycia, albo gdy przypadek użycia musi skoordynować transakcję, zapis i powiadomienia wokół logiki domenowej. Pomiń, gdy klient jest jeden i Domain Model można wołać wprost z kontrolera.
