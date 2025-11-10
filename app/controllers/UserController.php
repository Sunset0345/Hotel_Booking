<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class UserController extends Controller {
    public function __construct()
    {
        parent::__construct();
        $this->call->model('RoomsModel');
        $this->call->model('BookingsModel');
        $this->call->model('MessagesModel');
    }

    public function index() {
        if(!$this->session->has_userdata('user')) {
            redirect(site_url('auth/login'));
            return;
        }
        $user = $this->session->userdata('user');
        // load messages for embedding on the user dashboard so conversation is visible at /user
        $this->call->model('MessagesModel');
        try{
            $stmt = $this->MessagesModel->raw('SELECT m.*, u.full_name FROM messages m LEFT JOIN users u ON u.user_id = m.user_id WHERE m.user_id = ? ORDER BY m.date_sent ASC', [intval($user['user_id'])]);
            $conversation = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e){
            $conversation = [];
        }
        $rooms = $this->RoomsModel->All();
        // annotate rooms with booking state: available | pending | booked
        $today = date('Y-m-d');
        if(!empty($rooms)){
            foreach($rooms as &$r){
                $r['booking_state'] = 'available';
                try{
                    // check for approved bookings that are still relevant (not already past)
                    $sql = 'SELECT status, check_in, check_out FROM bookings WHERE room_id = ? AND check_out >= ? AND status IN (?, ?) ORDER BY date_booked DESC';
                    $stmt = $this->BookingsModel->raw($sql, [intval($r['room_id']), $today, 'approved', 'pending']);
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if(!empty($rows)){
                        // if any approved exists, mark as booked (hide from users)
                        foreach($rows as $row){
                            if(strtolower($row['status']) === 'approved'){
                                $r['booking_state'] = 'booked';
                                break 2;
                            }
                        }
                        // otherwise if pending exists, mark as pending (transparent)
                        foreach($rows as $row){
                            if(strtolower($row['status']) === 'pending'){
                                $r['booking_state'] = 'pending';
                                break;
                            }
                        }
                    }
                }catch(Exception $e){
                    // ignore DB errors here; default to available
                }
            }
            unset($r);
        }

        $this->call->view('user/index', ['user' => $user, 'rooms' => $rooms, 'conversation' => $conversation]);
    }

    public function profile()
    {
        if(!$this->session->has_userdata('user')){
            // If this is an AJAX request, respond with JSON 401 instead of redirecting
            try{
                if($this->io->is_ajax()){
                    header('Content-Type: application/json');
                    http_response_code(401);
                    echo json_encode(['status' => 'error', 'error' => 'unauthenticated', 'message' => 'Authentication required']);
                    return;
                }
            } catch(Exception $e){ /* if io not available, fall back to redirect */ }
            redirect(site_url('auth/login'));
            return;
        }
        $user = $this->session->userdata('user');
        $this->call->view('user/profile', ['user' => $user]);
    }

    public function bookings()
    {
        if(!$this->session->has_userdata('user')){ redirect(site_url('auth/login')); return; }
        $user = $this->session->userdata('user');
        // fetch bookings for this user with room info
        $sql = "SELECT b.*, r.room_number, r.room_type FROM bookings b LEFT JOIN rooms r ON r.room_id = b.room_id WHERE b.user_id = ? ORDER BY b.date_booked DESC";
        $stmt = $this->BookingsModel->raw($sql, [intval($user['user_id'])]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->call->view('user/bookings', ['user' => $user, 'bookings' => $bookings]);
    }

    public function messages()
    {
        // If not authenticated, return JSON 401 for AJAX callers to avoid an HTML redirect
        if(!$this->session->has_userdata('user')){
            try{
                if($this->io->is_ajax()){
                    header('Content-Type: application/json');
                    http_response_code(401);
                    echo json_encode(['status' => 'login_required', 'message' => 'Authentication required']);
                    return;
                }
            } catch(Exception $e){ /* fallback to redirect if io not available */ }
            redirect(site_url('auth/login')); return;
        }
        $user = $this->session->userdata('user');
        // Diagnostic logging for AJAX troubleshooting
        try{
            $projectRoot = dirname(__DIR__, 2);
            $logDir = $projectRoot . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'logs';
            if(!is_dir($logDir)) @mkdir($logDir, 0755, true);
            $entry = "[".date('Y-m-d H:i:s')."] UserController::messages()\n";
            $entry .= "REMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? 'cli') . "\n";
            $entry .= "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? '') . "\n";
            $entry .= "METHOD: " . ($this->io->method() ?? ($_SERVER['REQUEST_METHOD'] ?? 'unknown')) . "\n";
            $entry .= "is_ajax: " . ($this->io->is_ajax() ? '1' : '0') . "\n";
            $entry .= "session_user: " . ($this->session->has_userdata('user') ? $this->session->userdata('user')['user_id'] : 'none') . "\n";
            $entry .= "GET: " . json_encode($_GET) . "\n";
            $entry .= "POST: " . json_encode($_POST) . "\n";
            $entry .= "COOKIES: " . json_encode($_COOKIE) . "\n\n";
            @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'user-messages-api.log', $entry, FILE_APPEND | LOCK_EX);
        } catch(Exception $e) { /* ignore logging errors */ }
        // Quick DB connectivity check — return friendly error if DB is unavailable
        try{
            $this->MessagesModel->raw('SELECT 1');
        } catch(Exception $e) {
            if($this->io->is_ajax()){
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['status' => 'error', 'msg' => 'Database connection error', 'detail' => $e->getMessage()]);
                return;
            }
            // For regular page loads, render a simple message so UI shows diagnostic
            $data = ['user' => $user, 'conversation' => [], 'error' => 'Unable to connect to the database. Please check server configuration.'];
            $this->call->view('user/messages', $data);
            return;
        }
        // If POST, create a message from user to admin
        if($this->io->method() === 'post'){
            $message = trim($this->io->post('message'));
            if(!empty($message)){
                $this->MessagesModel->insert(['from_admin' => 0, 'user_id' => intval($user['user_id']), 'message' => $message]);
                // If AJAX, return a small JSON payload so the client can update the UI without a full reload
                if($this->io->is_ajax()){
                    $resp = [
                        'status' => 'ok',
                        'message' => $message,
                        'date_sent' => date('Y-m-d H:i:s')
                    ];
                    header('Content-Type: application/json');
                    echo json_encode($resp);
                    return;
                }
            }
        }
        // Fetch full conversation for this user (both admin and user messages)
        $stmt = $this->MessagesModel->raw('SELECT m.*, u.full_name FROM messages m LEFT JOIN users u ON u.user_id = m.user_id WHERE m.user_id = ? ORDER BY m.date_sent ASC', [intval($user['user_id'])]);
        $conversation = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Instrumentation: log conversation size even for non-AJAX (page view) to diagnose empty UI
        try {
            $projectRoot = dirname(__DIR__, 2);
            $logDir = $projectRoot . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'logs';
            if(!is_dir($logDir)) @mkdir($logDir, 0755, true);
            $snippet = [];
            if(!empty($conversation)){
                foreach(array_slice($conversation, 0, 3) as $c){
                    $snippet[] = [
                        'id' => $c['message_id'] ?? null,
                        'from_admin' => $c['from_admin'] ?? null,
                        'date_sent' => $c['date_sent'] ?? null,
                        'message' => mb_substr($c['message'] ?? '', 0, 60)
                    ];
                }
            }
            $convLog  = "[".date('Y-m-d H:i:s')."] conversation_fetch user_id=".intval($user['user_id'])." count=".count($conversation)."\n";
            $convLog .= 'snippet=' . json_encode($snippet) . "\n";
            // If count is zero, do a quick sanity check to see if ANY rows exist in messages table for transparency
            if(count($conversation) === 0){
                try {
                    $probe = $this->MessagesModel->raw('SELECT user_id, from_admin, message, date_sent FROM messages ORDER BY message_id DESC LIMIT 5');
                    $probeRows = $probe->fetchAll(PDO::FETCH_ASSOC);
                    $convLog .= 'probe_rows=' . json_encode($probeRows) . "\n";
                } catch(Exception $ie) {
                    $convLog .= 'probe_error=' . $ie->getMessage() . "\n";
                }
            }
            @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'user-messages-api.log', $convLog."\n", FILE_APPEND | LOCK_EX);
        } catch(Exception $e) { /* ignore */ }

        // If this is an AJAX GET (chat panel requesting conversation), mark admin->user messages as read
        if ($this->io->is_ajax() && $this->io->method() === 'get') {
            try{
                $this->MessagesModel->raw('UPDATE messages SET is_read = 1 WHERE user_id = ? AND from_admin = 1', [intval($user['user_id'])]);
            } catch(Exception $e){ /* ignore if column missing or update fails */ }
        }
        // If this is an AJAX request, support JSON responses for the chat panel
        if($this->io->is_ajax() && $this->io->method() === 'get'){
            // If requesting weeks summary (history index)
            if($this->io->get('weeks')){
                // return last 12 weeks start/end and counts
                $weeks = [];
                $now = new DateTime();
                // align to start of this week (Monday)
                $now->setTime(0,0,0);
                $dayOfWeek = (int)$now->format('N'); // 1 (Mon) - 7 (Sun)
                $now->modify('-'.($dayOfWeek-1).' days');
                for($i = 0; $i < 12; $i++){
                    $start = clone $now;
                    $start->modify('-'.($i*7).' days');
                    $end = clone $start; $end->modify('+6 days');
                    $startStr = $start->format('Y-m-d') . ' 00:00:00';
                    $endStr = $end->format('Y-m-d') . ' 23:59:59';
                    try{
                        $stmt = $this->MessagesModel->raw('SELECT COUNT(*) AS cnt FROM messages WHERE user_id = ? AND date_sent BETWEEN ? AND ?', [intval($user['user_id']), $startStr, $endStr]);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $cnt = isset($row['cnt']) ? intval($row['cnt']) : 0;
                    } catch(Exception $e) {
                        $cnt = 0;
                    }
                    $weeks[] = [
                        'start' => $start->format('Y-m-d'),
                        'end' => $end->format('Y-m-d'),
                        'label' => $start->format('M j, Y') . ' — ' . $end->format('M j, Y'),
                        'count' => $cnt
                    ];
                }
                $resp = ['status' => 'ok', 'weeks' => $weeks];
                // log response for debugging
                try{
                    $projectRoot = dirname(__DIR__, 2);
                    $logDir = $projectRoot . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'logs';
                    if(!is_dir($logDir)) @mkdir($logDir, 0755, true);
                    @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'user-messages-api.log', "[".date('Y-m-d H:i:s')."] response weeks\n" . json_encode($resp) . "\n\n", FILE_APPEND | LOCK_EX);
                } catch(Exception $e) { }
                header('Content-Type: application/json');
                echo json_encode($resp);
                return;
            }

            // If requesting a specific week range
            $weekStart = $this->io->get('week_start');
            if($weekStart){
                // sanitize and compute end
                try{
                    $s = new DateTime($weekStart);
                    $s->setTime(0,0,0);
                    $e = clone $s; $e->modify('+6 days'); $e->setTime(23,59,59);
                    $stmt = $this->MessagesModel->raw('SELECT m.*, u.full_name FROM messages m LEFT JOIN users u ON u.user_id = m.user_id WHERE m.user_id = ? AND m.date_sent BETWEEN ? AND ? ORDER BY m.date_sent ASC', [intval($user['user_id']), $s->format('Y-m-d H:i:s'), $e->format('Y-m-d H:i:s')]);
                    $conversation = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch(Exception $e){
                    $conversation = [];
                }
                $resp = ['status' => 'ok', 'conversation' => $conversation];
                try{
                    $projectRoot = dirname(__DIR__, 2);
                    $logDir = $projectRoot . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'logs';
                    if(!is_dir($logDir)) @mkdir($logDir, 0755, true);
                    @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'user-messages-api.log', "[".date('Y-m-d H:i:s')."] response week_conversation\n" . json_encode($resp) . "\n\n", FILE_APPEND | LOCK_EX);
                } catch(Exception $e) { }
                header('Content-Type: application/json');
                echo json_encode($resp);
                return;
            }

            // default: return entire conversation
            $resp = ['status' => 'ok', 'conversation' => $conversation];
            try{
                $projectRoot = dirname(__DIR__, 2);
                $logDir = $projectRoot . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'logs';
                if(!is_dir($logDir)) @mkdir($logDir, 0755, true);
                @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'user-messages-api.log', "[".date('Y-m-d H:i:s')."] response conversation\n" . json_encode($resp) . "\n\n", FILE_APPEND | LOCK_EX);
            } catch(Exception $e) { }
            header('Content-Type: application/json');
            echo json_encode($resp);
            return;
        }

        $this->call->view('user/messages', ['user' => $user, 'conversation' => $conversation]);
    }
}