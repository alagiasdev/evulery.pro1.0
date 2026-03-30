<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class OrderItem
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Crea in batch tutte le righe di un ordine (snapshot prezzi).
     */
    public function createBatch(int $orderId, array $items): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO order_items (order_id, menu_item_id, item_name, quantity, unit_price, notes)
             VALUES (:order_id, :menu_item_id, :item_name, :quantity, :unit_price, :notes)'
        );

        foreach ($items as $item) {
            $stmt->execute([
                'order_id'     => $orderId,
                'menu_item_id' => $item['menu_item_id'] ?? null,
                'item_name'    => $item['item_name'],
                'quantity'     => (int)($item['quantity'] ?? 1),
                'unit_price'   => (float)$item['unit_price'],
                'notes'        => $item['notes'] ?? null,
            ]);
        }
    }

    /**
     * Ritorna tutte le righe di un ordine.
     */
    public function findByOrder(int $orderId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM order_items WHERE order_id = :order_id ORDER BY id ASC'
        );
        $stmt->execute(['order_id' => $orderId]);
        return $stmt->fetchAll();
    }
}
