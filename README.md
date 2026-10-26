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

#### Row Data Gateway – `src/Enterprise/DataSource/RowDataGateway`
One object per row of `medications`: `MedicationRow` carries the columns as public fields and knows how to `insert()`, `update()` and `delete()` itself, while `MedicationFinder` runs the SELECTs and builds row objects. Still no business logic: the row object is a typed handle on a database record, nothing more.

Use it when Transaction Scripts prefer typed objects over arrays and each row maps to one record. The moment you want to add rules to the row object (`dispense()`), you have an Active Record; with a Domain Model you need a Data Mapper instead.

#### Active Record – `src/Enterprise/DataSource/ActiveRecord`
`Medication` is a row of `medications` that also carries its rules: `dispense()` checks and reduces stock and returns the amount to pay, `restock()` adds units, while `find()` and `save()` (an upsert) talk to PDO from inside the same class. One class, no mapping layer, but the domain object cannot exist without a database connection.

Use it when the domain is simple and objects map one-to-one onto tables, which is why Laravel's Eloquent and Rails are built on it. As rules grow and objects stop matching rows, the mix of SQL and logic makes tests slow and refactoring hard; that is when a Domain Model with a Data Mapper pays off.

#### Data Mapper – `src/Enterprise/DataSource/DataMapper`
`MedicationMapper`, `PrescriptionMapper` and `SaleMapper` implement the Service Layer's repository interfaces on `PharmacySchema`: a mapper reads rows and assembles Domain Model objects (`Prescription` with its items and their medications), then takes changed objects apart into UPDATE and INSERT statements. The domain classes stay free of PDO, and `PdoTransactionManager` closes the story: `PharmacyService` now runs end to end on SQLite, with the same tests that previously ran on in-memory fakes.

Use it with a Domain Model whose objects do not map one-to-one onto tables, or when the domain must be testable without a database. For simple CRUD on flat tables it is more code than an Active Record for no benefit; Doctrine ORM is a general-purpose Data Mapper, so in PHP you usually configure one rather than write one.

### Object-Relational

#### Identity Map – `src/Enterprise/ObjectRelational/IdentityMap`
A prescription lists the same insulin twice (morning and evening dose, different refund levels). Without a map, the Data Mapper loads two `Medication` objects for one row, each reduces its own copy of the stock and the second `save()` overwrites the first: a lost update inside a single transaction. `IdentityMap` keeps one object per class and id, and the mapper's `get()` asks the map before the database, so both items share one object and the stock ends up correct. The test shows both outcomes side by side.

Use it whenever a Domain Model is loaded through mappers and the same row can be reached twice within one unit of work; it also removes repeated SELECTs. Scope it to one transaction or request, because a longer-lived map serves stale objects. Doctrine's `EntityManager` has one built in.

#### Unit of Work – `src/Enterprise/ObjectRelational/UnitOfWork`
`UnitOfWork` collects what one business transaction touched (`registerDirty()` for loaded medications and prescriptions, `registerNew()` for the sale) and `commit()` writes each object exactly once through its mapper, inside one database transaction, then clears itself. The `PharmacyService` here is the Service Layer's one rewritten on top of it: no `transactional()` closure, no save-per-line loop, just register and commit. Paired with the Identity Map, the same medication registered from two prescription items is written once.

Use it when a use case changes several objects and you want persistence decided in one place, with duplicates removed and the transaction boundary owned by the unit, not by each service method. For a single-object change it is ceremony; Doctrine's `EntityManager::flush()` is this pattern with automatic dirty checking, so in PHP you usually use that rather than write one.

#### Lazy Load – `src/Enterprise/ObjectRelational/LazyLoad`
A dispenser's list of valid prescriptions needs only ids and validity dates, not items and medications. `PrescriptionFinder::findValidOn()` runs one query and returns native lazy proxies (PHP 8.4+ `ReflectionClass::newLazyProxy()`): `id` and `validUntil` are pre-filled from the list row, and the first touch of anything else (`dispense()`, `dispensedAt`) makes the Data Mapper load the full aggregate. The test counts prepared statements to prove when the database is hit, and the domain class is untouched.

Use it when a list or a parent object is loaded far more often than its expensive parts. Watch for ripple loading (one query per element in a loop, the N+1 problem) and for proxies escaping the unit of work that could still load them; Doctrine proxies are this pattern.

#### Embedded Value – `src/Enterprise/ObjectRelational/EmbeddedValue`
A courier `Invoice` holds two value objects, `Address` and `Money`, that have no identity and no table of their own: `InvoiceMapper` flattens them into columns of the `invoices` row (`recipient_street`, `recipient_city`, `net_amount_in_cents`, `currency`) and rebuilds the objects when loading. The domain keeps `$invoice->recipient->city` and `$invoice->grossAmount` while the database sees one plain row.

Use it for small immutable value objects that belong to exactly one owner, especially money, addresses and date ranges. When the value needs identity, is shared by several owners or is a collection, it belongs in its own table with a foreign key. In Doctrine this is `#[Embedded]`.

#### Single Table Inheritance – `src/Enterprise/ObjectRelational/InheritanceMapping/SingleTable`
A logistics hierarchy `Shipment` → `ParcelShipment` (weight) / `PalletShipment` (pallet count, EUR-pallet exchange) lives in one `shipments` table: a `type` discriminator column plus nullable columns for every subclass field. `SingleTableShipmentMapper` writes the subclass fields it has and `NULL` for the rest, and rebuilds the right subclass from `type` with one plain `SELECT` for both `find()` and `findBySender()`.

Use it when subclasses add few fields and polymorphic queries dominate: one table, no joins, fastest reads. The cost is a wide table of nullable columns and the loss of `NOT NULL` constraints on subclass data; with many or heavy subclasses switch to Class Table Inheritance. In Doctrine this is `#[InheritanceType('SINGLE_TABLE')]`.

#### Class Table Inheritance – `src/Enterprise/ObjectRelational/InheritanceMapping/ClassTable`
The same hierarchy stored as one table per class: `shipments` (tracking number, type, sender) plus `parcel_shipments` and `pallet_shipments` sharing the tracking number as primary and foreign key. `ClassTableShipmentMapper::insert()` writes two rows, and every read `LEFT JOIN`s all subclass tables and picks the meaningful columns by `type`. The test suite runs the same behaviour tests through both mappers with a `#[DataProvider]`; only the table-layout tests differ.

Use it when subclasses carry many fields that deserve `NOT NULL` constraints and their own indexes, or when the schema is shared with other applications that read one subclass table. The cost is a join per level on every read and a multi-row insert, which `ClassTableShipmentMapper` wraps in `TransactionManager::transactional()`. In Doctrine this is `#[InheritanceType('JOINED')]`.

#### Association Table Mapping – `src/Enterprise/ObjectRelational/AssociationTableMapping`
A courier's `DeliveryRoute` carries many `Shipment`s and a shipment can move between routes after a failed delivery: a many-to-many with no object of its own. The `route_shipments` table holds only two foreign keys; `DeliveryRouteMapper::find()` loads the shipments through a `JOIN` over it, and `save()` diffs the in-memory shipments against the stored rows, deleting the unloaded and inserting the newly loaded ones.

Use it for many-to-many links that carry no data of their own. The moment the link gets attributes (loaded at, scan position, who signed) it becomes an entity with its own class and mapper. In Doctrine this is `#[ManyToMany]` with `#[JoinTable]`.

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

#### Row Data Gateway – `src/Enterprise/DataSource/RowDataGateway`
Jeden obiekt na wiersz tabeli `medications`: `MedicationRow` ma kolumny jako publiczne pola i umie sam zrobić `insert()`, `update()` i `delete()`, a `MedicationFinder` wykonuje SELECT-y i buduje obiekty wierszy. Nadal bez logiki biznesowej: obiekt wiersza to typowany uchwyt na rekord bazy, nic więcej.

Stosuj, gdy Transaction Scripty wolą typowane obiekty od tablic, a każdy wiersz odpowiada jednemu rekordowi. W chwili, gdy chcesz dodać do obiektu wiersza reguły (`dispense()`), masz Active Record; przy Domain Model potrzebujesz Data Mappera.

#### Active Record – `src/Enterprise/DataSource/ActiveRecord`
`Medication` to wiersz tabeli `medications`, który niesie też swoje reguły: `dispense()` sprawdza i zdejmuje stan oraz zwraca kwotę do zapłaty, `restock()` dokłada opakowania, a `find()` i `save()` (upsert) rozmawiają z PDO z wnętrza tej samej klasy. Jedna klasa, zero warstwy mapowania, ale obiekt domenowy nie istnieje bez połączenia z bazą.

Stosuj, gdy domena jest prosta, a obiekty mapują się jeden do jednego na tabele; dlatego na tym wzorcu stoją Eloquent z Laravela i Rails. Gdy reguł przybywa i obiekty przestają odpowiadać wierszom, mieszanka SQL i logiki spowalnia testy i utrudnia refaktor; wtedy opłaca się Domain Model z Data Mapperem.

#### Data Mapper – `src/Enterprise/DataSource/DataMapper`
`MedicationMapper`, `PrescriptionMapper` i `SaleMapper` implementują interfejsy repozytoriów z Service Layer na `PharmacySchema`: mapper czyta wiersze i składa z nich obiekty Domain Model (`Prescription` z pozycjami i ich lekami), a zmienione obiekty rozkłada z powrotem na UPDATE i INSERT. Klasy domenowe nie znają PDO, a `PdoTransactionManager` domyka historię: `PharmacyService` działa teraz od końca do końca na SQLite, z tymi samymi testami, które wcześniej chodziły na fake'ach in-memory.

Stosuj z Domain Model, którego obiekty nie mapują się jeden do jednego na tabele, albo gdy domena ma być testowalna bez bazy. Przy prostym CRUD na płaskich tabelach to więcej kodu niż Active Record bez zysku; Doctrine ORM to ogólny Data Mapper, więc w PHP zwykle go konfigurujesz, a nie piszesz.

### Obiektowo-relacyjne

#### Identity Map – `src/Enterprise/ObjectRelational/IdentityMap`
Recepta ma tę samą insulinę na dwóch pozycjach (dawka poranna i wieczorna, różne poziomy refundacji). Bez mapy Data Mapper wczytuje dwa obiekty `Medication` dla jednego wiersza, każdy zdejmuje stan ze swojej kopii, a drugi `save()` nadpisuje pierwszy: lost update wewnątrz jednej transakcji. `IdentityMap` trzyma jeden obiekt na klasę i id, a `get()` mappera pyta najpierw mapę, potem bazę, więc obie pozycje dzielą jeden obiekt i stan wychodzi poprawny. Test pokazuje oba wyniki obok siebie.

Stosuj zawsze, gdy Domain Model jest wczytywany przez mappery i ten sam wiersz może być sięgnięty dwa razy w jednej jednostce pracy; przy okazji znikają powtórne SELECT-y. Ogranicz jej życie do jednej transakcji lub żądania, bo dłużej żyjąca mapa serwuje przestarzałe obiekty. `EntityManager` Doctrine ma ją wbudowaną.

#### Unit of Work – `src/Enterprise/ObjectRelational/UnitOfWork`
`UnitOfWork` zbiera to, czego dotknęła jedna transakcja biznesowa (`registerDirty()` dla wczytanych leków i recept, `registerNew()` dla sprzedaży), a `commit()` zapisuje każdy obiekt dokładnie raz przez jego mapper, w jednej transakcji bazodanowej, po czym się czyści. Tutejszy `PharmacyService` to serwis z Service Layer przepisany na tę jednostkę: bez closure `transactional()`, bez pętli zapisu per linia, tylko rejestracja i commit. W parze z Identity Map ten sam lek zarejestrowany z dwóch pozycji recepty zapisuje się raz.

Stosuj, gdy przypadek użycia zmienia kilka obiektów i chcesz, żeby o zapisie decydowało jedno miejsce, z usuniętymi duplikatami i granicą transakcji należącą do jednostki, a nie do każdej metody serwisu. Przy zmianie jednego obiektu to zbędna ceremonia; `EntityManager::flush()` Doctrine to ten wzorzec z automatycznym wykrywaniem zmian, więc w PHP zwykle używasz jego, a nie piszesz własnego.

#### Lazy Load – `src/Enterprise/ObjectRelational/LazyLoad`
Lista ważnych recept u farmaceuty potrzebuje tylko id i dat ważności, nie pozycji i leków. `PrescriptionFinder::findValidOn()` wykonuje jedno zapytanie i zwraca natywne lazy proxy (PHP 8.4+ `ReflectionClass::newLazyProxy()`): `id` i `validUntil` są wypełnione z wiersza listy, a pierwsze dotknięcie czegokolwiek innego (`dispense()`, `dispensedAt`) każe Data Mapperowi wczytać cały agregat. Test liczy prepared statements, żeby udowodnić, kiedy baza jest odpytywana, a klasa domenowa pozostaje nietknięta.

Stosuj, gdy lista albo obiekt nadrzędny są wczytywane dużo częściej niż ich kosztowne części. Uważaj na ładowanie kaskadowe (zapytanie per element w pętli, problem N+1) i na proxy wyciekające poza jednostkę pracy, która mogłaby je jeszcze doładować; proxy Doctrine to ten wzorzec.

#### Embedded Value – `src/Enterprise/ObjectRelational/EmbeddedValue`
Faktura kurierska `Invoice` zawiera dwa value objecty, `Address` i `Money`, które nie mają tożsamości ani własnej tabeli: `InvoiceMapper` rozpłaszcza je do kolumn wiersza `invoices` (`recipient_street`, `recipient_city`, `net_amount_in_cents`, `currency`) i odbudowuje obiekty przy wczytaniu. Domena ma `$invoice->recipient->city` i `$invoice->grossAmount`, a baza widzi jeden zwykły wiersz.

Stosuj dla małych, niemutowalnych value objectów należących do dokładnie jednego właściciela, zwłaszcza pieniędzy, adresów i zakresów dat. Gdy wartość potrzebuje tożsamości, jest dzielona przez kilku właścicieli albo jest kolekcją, należy jej się własna tabela z kluczem obcym. W Doctrine to `#[Embedded]`.

#### Single Table Inheritance – `src/Enterprise/ObjectRelational/InheritanceMapping/SingleTable`
Hierarchia logistyczna `Shipment` → `ParcelShipment` (waga) / `PalletShipment` (liczba palet, wymiana EUR-palet) mieszka w jednej tabeli `shipments`: kolumna dyskryminatora `type` plus nullowalne kolumny dla pól każdej podklasy. `SingleTableShipmentMapper` zapisuje pola, które ma, i `NULL` w pozostałych, a odpowiednią podklasę odbudowuje z `type` jednym zwykłym `SELECT` zarówno w `find()`, jak i `findBySender()`.

Stosuj, gdy podklasy dodają niewiele pól, a dominują zapytania polimorficzne: jedna tabela, bez złączeń, najszybsze odczyty. Kosztem jest szeroka tabela nullowalnych kolumn i utrata ograniczeń `NOT NULL` na danych podklas; przy wielu lub ciężkich podklasach przejdź na Class Table Inheritance. W Doctrine to `#[InheritanceType('SINGLE_TABLE')]`.

#### Class Table Inheritance – `src/Enterprise/ObjectRelational/InheritanceMapping/ClassTable`
Ta sama hierarchia zapisana jako jedna tabela na klasę: `shipments` (numer przesyłki, typ, nadawca) oraz `parcel_shipments` i `pallet_shipments` dzielące numer przesyłki jako klucz główny i obcy. `ClassTableShipmentMapper::insert()` zapisuje dwa wiersze, a każdy odczyt robi `LEFT JOIN` ze wszystkimi tabelami podklas i wybiera znaczące kolumny po `type`. Testy zachowania przechodzą przez oba mappery z `#[DataProvider]`; różnią się tylko testy układu tabel.

Stosuj, gdy podklasy niosą wiele pól zasługujących na `NOT NULL` i własne indeksy, albo gdy schemat dzielą inne aplikacje czytające tabelę jednej podklasy. Kosztem jest złączenie na każdym poziomie przy każdym odczycie i wielowierszowy insert, który `ClassTableShipmentMapper` owija w `TransactionManager::transactional()`. W Doctrine to `#[InheritanceType('JOINED')]`.

#### Association Table Mapping – `src/Enterprise/ObjectRelational/AssociationTableMapping`
Trasa kuriera `DeliveryRoute` wozi wiele `Shipment`, a przesyłka po nieudanym doręczeniu może przejść na inną trasę: wiele-do-wielu bez własnego obiektu. Tabela `route_shipments` ma tylko dwa klucze obce; `DeliveryRouteMapper::find()` ładuje przesyłki przez `JOIN` po niej, a `save()` porównuje przesyłki w pamięci z zapisanymi wierszami, usuwając wyładowane i wstawiając nowo załadowane.

Stosuj dla powiązań wiele-do-wielu bez własnych danych. Gdy powiązanie dostaje atrybuty (kiedy załadowano, pozycja skanu, kto podpisał), staje się encją z własną klasą i mapperem. W Doctrine to `#[ManyToMany]` z `#[JoinTable]`.
