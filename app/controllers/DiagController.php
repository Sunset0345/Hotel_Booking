<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class DiagController extends Controller {
    // simple DB health check returning JSON
    public function db_health()
    {
        // allow local testing from browser; do not require login
        header('Content-Type: application/json');
        try {
            // attempt to create database connection using framework Database class
            $db = new Database();
            // run a tiny harmless query
            $stmt = $db->raw('SELECT 1 AS ok');
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'ok', 'db' => true, 'result' => $row]);
            return;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'db' => false, 'msg' => $e->getMessage()]);
            return;
        }
    }
}
