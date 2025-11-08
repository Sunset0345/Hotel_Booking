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

        $this->call->view('user/index', ['user' => $user, 'rooms' => $rooms]);
    }

    public function profile()
    {
        if(!$this->session->has_userdata('user')){ redirect(site_url('auth/login')); return; }
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
        if(!$this->session->has_userdata('user')){ redirect(site_url('auth/login')); return; }
        $user = $this->session->userdata('user');
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
        $stmt = $this->MessagesModel->raw('SELECT m.*, u.full_name FROM messages m LEFT JOIN users u ON u.user_id = m.user_id WHERE m.user_id = ? ORDER BY m.date_sent ASC', [intval($user['user_id'])]);
        $conversation = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                header('Content-Type: application/json');
                echo json_encode(['status' => 'ok', 'weeks' => $weeks]);
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
                header('Content-Type: application/json');
                echo json_encode(['status' => 'ok', 'conversation' => $conversation]);
                return;
            }

            // default: return entire conversation
            header('Content-Type: application/json');
            echo json_encode(['status' => 'ok', 'conversation' => $conversation]);
            return;
        }

        $this->call->view('user/messages', ['user' => $user, 'conversation' => $conversation]);
    }
}