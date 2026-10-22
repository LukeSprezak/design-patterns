<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\AssociationTableMapping;

use PDO;

final readonly class DeliveryRouteMapper
{
    public function __construct(private PDO $connection)
    {
    }

    public function insert(DeliveryRoute $route): void
    {
        $this->connection
            ->prepare('INSERT INTO delivery_routes (id, courier_name) VALUES (?, ?)')
            ->execute([$route->id, $route->courierName]);
        $this->insertAssociations($route->id, array_keys($route->shipments));
    }

    public function find(int $id): ?DeliveryRoute
    {
        $routeStatement = $this->connection->prepare('SELECT id, courier_name FROM delivery_routes WHERE id = ?');
        $routeStatement->execute([$id]);
        $routeRow = $routeStatement->fetch(PDO::FETCH_ASSOC);
        if ($routeRow === false) {
            return null;
        }

        $shipmentsStatement = $this->connection->prepare(<<<SQL
            SELECT shipments.tracking_number, shipments.recipient_city
            FROM route_shipments
            JOIN shipments ON shipments.tracking_number = route_shipments.tracking_number
            WHERE route_shipments.route_id = ?
            ORDER BY shipments.tracking_number
            SQL);
        $shipmentsStatement->execute([$id]);
        $shipments = array_map(
            static fn (array $shipmentRow): Shipment => new Shipment($shipmentRow['tracking_number'], $shipmentRow['recipient_city']),
            $shipmentsStatement->fetchAll(PDO::FETCH_ASSOC),
        );

        return new DeliveryRoute($routeRow['id'], $routeRow['courier_name'], ...$shipments);
    }

    /** Diffs the in-memory shipments against the stored association rows and writes only the difference. */
    public function save(DeliveryRoute $route): void
    {
        $storedStatement = $this->connection->prepare('SELECT tracking_number FROM route_shipments WHERE route_id = ?');
        $storedStatement->execute([$route->id]);
        $storedTrackingNumbers = $storedStatement->fetchAll(PDO::FETCH_COLUMN);
        $currentTrackingNumbers = array_keys($route->shipments);

        $deleteStatement = $this->connection->prepare('DELETE FROM route_shipments WHERE route_id = ? AND tracking_number = ?');
        foreach (array_diff($storedTrackingNumbers, $currentTrackingNumbers) as $unloadedTrackingNumber) {
            $deleteStatement->execute([$route->id, $unloadedTrackingNumber]);
        }
        $this->insertAssociations($route->id, array_diff($currentTrackingNumbers, $storedTrackingNumbers));
    }

    /** @param iterable<string> $trackingNumbers */
    private function insertAssociations(int $routeId, iterable $trackingNumbers): void
    {
        $insertStatement = $this->connection->prepare('INSERT INTO route_shipments (route_id, tracking_number) VALUES (?, ?)');
        foreach ($trackingNumbers as $trackingNumber) {
            $insertStatement->execute([$routeId, $trackingNumber]);
        }
    }
}
