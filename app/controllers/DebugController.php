<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class DebugController extends Controller {
    public function __construct()
    {
        parent::__construct();
    }

    // Simple JSON endpoint to inspect session and cookies for debugging AJAX
    public function session()
    {
        // write a server-side hit log for debugging
        try{
            $projectRoot = dirname(__DIR__, 2);
            $logDir = $projectRoot . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'logs';
            if(!is_dir($logDir)) @mkdir($logDir, 0755, true);
            $entry = "[".date('Y-m-d H:i:s')."] DebugController::session() hit\nREQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? '') . "\nREMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? '') . "\nCOOKIES: " . json_encode($_COOKIE) . "\n\n";
            @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'debug-session.log', $entry, FILE_APPEND | LOCK_EX);
        } catch(Exception $e) { }

        // include server-side session userdata (non-sensitive) for debugging
        $sessionUser = null;
        try{
            if($this->session->has_userdata('user')){
                $u = $this->session->userdata('user');
                // only expose non-sensitive fields
                $sessionUser = [
                    'user_id' => $u['user_id'] ?? null,
                    'full_name' => $u['full_name'] ?? null,
                    'email' => $u['email'] ?? null
                ];
            }
        } catch(Exception $e){ /* ignore */ }

        $out = [
            'time' => date('Y-m-d H:i:s'),
            'is_ajax' => $this->io->is_ajax() ? true : false,
            'has_user_session' => $this->session->has_userdata('user') ? true : false,
            'session_user' => $sessionUser,
            'cookies' => $_COOKIE,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET'
        ];

        header('Content-Type: application/json');
        echo json_encode($out);
    }

    // Echo back request headers/body for debugging AJAX requests
    public function echo()
    {
        $headers = [];
        foreach($_SERVER as $k=>$v){ if(strpos($k,'HTTP_')===0){ $headers[str_replace('HTTP_','', $k)] = $v; } }
        $raw = file_get_contents('php://input');
        $out = [
            'time' => date('Y-m-d H:i:s'),
            'is_ajax' => $this->io->is_ajax() ? true : false,
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'get' => $_GET,
            'post' => $_POST,
            'raw' => $raw,
            'cookies' => $_COOKIE,
            'headers' => $headers,
            'session_user' => ($this->session->has_userdata('user') ? $this->session->userdata('user') : null)
        ];

        // log echo hits for debugging
        try{
            $projectRoot = dirname(__DIR__, 2);
            $logDir = $projectRoot . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'logs';
            if(!is_dir($logDir)) @mkdir($logDir, 0755, true);
            @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'debug-echo.log', "[".date('Y-m-d H:i:s')."] DebugController::echo()\nREQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? '') . "\nMETHOD: " . ($_SERVER['REQUEST_METHOD'] ?? '') . "\nCOOKIES: " . json_encode($_COOKIE) . "\nGET: " . json_encode($_GET) . "\nPOST: " . json_encode($_POST) . "\nRAW: " . substr($raw,0,1000) . "\nSESSION_USER: " . json_encode($this->session->has_userdata('user') ? $this->session->userdata('user') : null) . "\n\n", FILE_APPEND | LOCK_EX);
        } catch(Exception $e) { }

        header('Content-Type: application/json');
        echo json_encode($out);
    }
}
