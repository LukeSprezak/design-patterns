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

#### Table Module – `src/Enterprise/DomainLogic/TableModule`
Monthly settlement of ambulance dispatches: one class per database table (`Tariffs`, `Dispatches`), one instance per class, methods that take a row id (`isBillable()`, `priceInCents()`) or work over the whole table (`settlementForMonth()`), with data staying as rows the entire time. Logic is organised by table rather than by request (Transaction Script) or by object (Domain Model).

Use it when logic is mostly tabular (rates, sums, filtering) and the platform provides a Record Set the UI and logic can share, as `DataSet` does in .NET. In PHP that infrastructure is missing, so a Domain Model or a Transaction Script almost always fits better; this one is here for completeness and comparison.

### Web Presentation

#### Front Controller – `src/Enterprise/WebPresentation/FrontController`
Fire station dispatcher panel without a framework: every request enters through `FrontController::handle()`, which checks the dispatcher token, matches the request against a route table (`GET /incidents`, `POST /incidents/{id}/{action}`), hands it to one `Command` and logs the outcome. Commands contain only their own job; authentication, routing, logging and 404 live in one place.

Use it when every request needs the same cross-cutting steps and you would otherwise copy them into each page script. In practice every framework ships one (Symfony `Kernel`, Laravel's `index.php`), so you write your own only in framework-free code or to understand what the framework does.

#### Application Controller – `src/Enterprise/WebPresentation/ApplicationController`
The incident lifecycle (`reported → dispatched → on_scene → closed`) as a state machine: `IncidentFlowController::handle()` takes the incident's state and the requested action, runs the matching domain command and returns the next view, for example `incident/request-backup` after arriving with too few units. Illegal transitions throw, and the Front Controller's `IncidentFlowCommand` turns that into a 409.

Use it when the order of screens depends on application state (wizards, case workflows) and that logic would otherwise spread across page controllers as `if` chains. When pages are independent and there is no "what next", plain page controllers are enough.

### Data Source

#### Table Data Gateway – `src/Enterprise/DataSource/TableDataGateway`
`MedicationGateway` is the only object that knows the SQL of the `medications` table: one instance per table, one method per statement (`find()`, `findBelowStock()`, `insert()`, `updateStock()`, `delete()`), rows passed in and out as plain arrays. It holds no business logic, so a Transaction Script or a Table Module can call it instead of embedding SQL.

Use it when logic lives in Transaction Scripts or Table Modules and you want SQL in one place per table. With a Domain Model you need a Data Mapper instead, because the gateway returns rows, not objects.

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

#### Table Module – `src/Enterprise/DomainLogic/TableModule`
Miesięczne rozliczenie wyjazdów pogotowia: jedna klasa na tabelę w bazie (`Tariffs`, `Dispatches`), jedna instancja na klasę, metody biorące id wiersza (`isBillable()`, `priceInCents()`) albo działające na całej tabeli (`settlementForMonth()`), a dane przez cały czas pozostają wierszami. Logika jest zorganizowana po tabelach, a nie po żądaniach (Transaction Script) ani po obiektach (Domain Model).

Stosuj, gdy logika jest głównie „tabelowa” (stawki, sumy, filtrowanie), a platforma daje Record Set współdzielony przez UI i logikę, jak `DataSet` w .NET. W PHP tej infrastruktury nie ma, więc Domain Model albo Transaction Script prawie zawsze pasują lepiej; ten wzorzec jest tu dla kompletu i porównania.

### Prezentacja webowa

#### Front Controller – `src/Enterprise/WebPresentation/FrontController`
Panel dyspozytora straży pożarnej bez frameworka: każde żądanie wchodzi przez `FrontController::handle()`, który sprawdza token dyspozytora, dopasowuje żądanie do tablicy tras (`GET /incidents`, `POST /incidents/{id}/{action}`), przekazuje je jednej komendzie `Command` i loguje wynik. Komendy zawierają tylko swoją robotę; uwierzytelnianie, routing, logowanie i 404 są w jednym miejscu.

Stosuj, gdy każde żądanie wymaga tych samych wspólnych kroków, które inaczej kopiowałbyś do każdego skryptu strony. W praktyce dostarcza go każdy framework (Symfony `Kernel`, `index.php` Laravela), więc własny piszesz tylko w kodzie bez frameworka albo po to, żeby zrozumieć, co framework robi.

#### Application Controller – `src/Enterprise/WebPresentation/ApplicationController`
Cykl życia zdarzenia (`reported → dispatched → on_scene → closed`) jako maszyna stanów: `IncidentFlowController::handle()` bierze stan zdarzenia i żądaną akcję, uruchamia pasującą komendę domenową i zwraca następny widok, np. `incident/request-backup` po dojeździe ze zbyt małą liczbą zastępów. Niedozwolone przejścia rzucają wyjątek, a `IncidentFlowCommand` z Front Controllera zamienia go na 409.

Stosuj, gdy kolejność ekranów zależy od stanu aplikacji (kreatory, obieg spraw), a ta logika rozlałaby się po kontrolerach stron w postaci łańcuchów `if`. Gdy strony są niezależne i nie ma pytania „co dalej”, wystarczą zwykłe kontrolery stron.

### Źródło danych

#### Table Data Gateway – `src/Enterprise/DataSource/TableDataGateway`
`MedicationGateway` to jedyny obiekt, który zna SQL tabeli `medications`: jedna instancja na tabelę, jedna metoda na zapytanie (`find()`, `findBelowStock()`, `insert()`, `updateStock()`, `delete()`), wiersze wchodzą i wychodzą jako zwykłe tablice. Nie ma logiki biznesowej, więc Transaction Script albo Table Module może go wołać zamiast wpisywać SQL u siebie.

Stosuj, gdy logika siedzi w Transaction Scriptach lub Table Modules i chcesz mieć SQL w jednym miejscu na tabelę. Przy Domain Model potrzebujesz Data Mappera, bo brama zwraca wiersze, a nie obiekty.
