<?php

namespace App\Models\SuperAdmin;

class PlanModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM plans ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    public function find($name) {
        $stmt = $this->db->prepare("SELECT * FROM plans WHERE name = ?");
        $stmt->execute([$name]);
        return $stmt->fetch();
    }

    public function save($data) {
        $stmt = $this->db->prepare("
            INSERT INTO plans (name, price_monthly, price_yearly, features_json)
            VALUES (:name, :price_m, :price_y, :features)
            ON DUPLICATE KEY UPDATE 
                price_monthly = :price_m2, 
                price_yearly = :price_y2, 
                features_json = :features2
        ");
        return $stmt->execute([
            'name' => $data['name'],
            'price_m' => $data['price_monthly'],
            'price_y' => $data['price_yearly'],
            'features' => json_encode($data['features']),
            'price_m2' => $data['price_monthly'],
            'price_y2' => $data['price_yearly'],
            'features2' => json_encode($data['features'])
        ]);
    }
}
