<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\ObjectRelational\AssociationTableMapping;

use DesignPatterns\Enterprise\ObjectRelational\AssociationTableMapping\DeliveryRoute;
use DesignPatterns\Enterprise\ObjectRelational\AssociationTableMapping\DeliveryRouteMapper;
use DesignPatterns\Enterprise\ObjectRelational\AssociationTableMapping\DeliverySchema;
use DesignPatterns\Enterprise\ObjectRelational\AssociationTableMapping\Shipment;
use DesignPatterns\Enterprise\DataSource\DataMapper\PdoTransactionManager;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DeliveryRouteMapper::class)]
#[CoversClass(DeliveryRoute::class)]
#[CoversClass(DeliverySchema::class)]
final class DeliveryRouteMapperTest extends TestCase
{
    private PDO $connection;
    private DeliveryRouteMapper $routes;
    private Shipment $parcelForWarsaw;
    private Shipment $parcelForKrakow;
    private Shipment $parcelForGdansk;

    protected function setUp(): void
    {
        $this->connection = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->connection->exec('PRAGMA foreign_keys = ON');
        DeliverySchema::create($this->connection);
        $this->connection->exec(<<<SQL
            INSERT INTO shipments (tracking_number, recipient_city) VALUES
                ('PARCEL-0001', 'Warsaw'),
                ('PARCEL-0002', 'Krakow'),
                ('PARCEL-0003', 'Gdansk');
            SQL);
        $this->routes = new DeliveryRouteMapper($this->connection, new PdoTransactionManager($this->connection));
        $this->parcelForWarsaw = new Shipment('PARCEL-0001', 'Warsaw');
        $this->parcelForKrakow = new Shipment('PARCEL-0002', 'Krakow');
        $this->parcelForGdansk = new Shipment('PARCEL-0003', 'Gdansk');
    }

    #[Test]
    public function findLoadsTheShipmentsOfTheRouteThroughTheAssociationTable(): void
    {
        $this->routes->insert(new DeliveryRoute(1, 'Anna Nowak', $this->parcelForWarsaw, $this->parcelForKrakow));
        $this->routes->insert(new DeliveryRoute(2, 'Jan Kowalski', $this->parcelForGdansk));

        $morningRoute = $this->routes->find(1);

        self::assertSame('Anna Nowak', $morningRoute->courierName);
        self::assertEquals(
            ['PARCEL-0001' => $this->parcelForWarsaw, 'PARCEL-0002' => $this->parcelForKrakow],
            $morningRoute->shipments,
        );
        self::assertNull($this->routes->find(3));
    }

    #[Test]
    public function saveSynchronisesTheAssociationRowsAfterAShipmentMovesToAnotherRoute(): void
    {
        $this->routes->insert(new DeliveryRoute(1, 'Anna Nowak', $this->parcelForWarsaw, $this->parcelForKrakow));
        $this->routes->insert(new DeliveryRoute(2, 'Jan Kowalski', $this->parcelForGdansk));
        $morningRoute = $this->routes->find(1);
        $afternoonRoute = $this->routes->find(2);

        $morningRoute->unload($this->parcelForKrakow);
        $afternoonRoute->load($this->parcelForKrakow);
        $this->routes->save($morningRoute);
        $this->routes->save($afternoonRoute);

        self::assertSame(
            [['route_id' => 1, 'tracking_number' => 'PARCEL-0001'], ['route_id' => 2, 'tracking_number' => 'PARCEL-0002'], ['route_id' => 2, 'tracking_number' => 'PARCEL-0003']],
            $this->connection->query('SELECT route_id, tracking_number FROM route_shipments ORDER BY route_id, tracking_number')->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    #[Test]
    public function saveRollsBackTheUnloadWhenLoadingAnUnknownShipmentFails(): void
    {
        $this->routes->insert(new DeliveryRoute(1, 'Anna Nowak', $this->parcelForWarsaw, $this->parcelForKrakow));
        $morningRoute = $this->routes->find(1);
        $morningRoute->unload($this->parcelForKrakow);
        $morningRoute->load(new Shipment('PARCEL-9999', 'Poznan'));

        try {
            $this->routes->save($morningRoute);
            self::fail('Linking a shipment that is not in the shipments table must violate the foreign key');
        } catch (PDOException) {
        }

        self::assertSame(
            ['PARCEL-0001', 'PARCEL-0002'],
            $this->connection->query('SELECT tracking_number FROM route_shipments ORDER BY tracking_number')->fetchAll(PDO::FETCH_COLUMN),
        );
    }

    #[Test]
    public function saveWritesTheNewCourierAfterTheRouteIsReassigned(): void
    {
        $this->routes->insert(new DeliveryRoute(1, 'Anna Nowak', $this->parcelForWarsaw));
        $morningRoute = $this->routes->find(1);

        $morningRoute->reassignTo('Piotr Zielinski');
        $this->routes->save($morningRoute);

        self::assertSame('Piotr Zielinski', $this->routes->find(1)->courierName);
    }
}
