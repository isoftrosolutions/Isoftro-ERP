<?php

namespace App\Http\Controllers\SuperAdmin;

class ProfileController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        include resource_path('views/super-admin/profile.php');
    }
}

