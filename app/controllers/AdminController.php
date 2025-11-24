<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class AdminController extends Controller {
    // AJAX endpoint for conversation fetch
    public function messages_ajax()
    {
        if(!$this->session->has_userdata('admin')){ echo '<div style="padding:18px;color:#999">Not authorized.</div>'; return; }
        $this->call->model('MessagesModel');
        $this->call->model('UsersModel');
        $user_id = intval($this->io->get('user_id')) ?: null;
        if(!$user_id){ echo '<div style="padding:18px;color:#999">No user selected.</div>'; return; }
        // mark user messages as read (messages from user to admin)
        try{
            $this->MessagesModel->raw('UPDATE messages SET is_read = 1 WHERE user_id = ? AND from_admin = 0', [$user_id]);
        } catch(Exception $e) {
            // ignore if the column doesn't exist yet
        }

        $stmt = $this->MessagesModel->raw('SELECT m.*, u.full_name FROM messages m JOIN users u ON u.user_id = m.user_id WHERE m.user_id = ? ORDER BY m.date_sent ASC', [$user_id]);
        $conversation = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // If this is an AJAX request and the client expects JSON, return JSON structured data
        if ($this->io->is_ajax()) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'ok', 'conversation' => $conversation]);
            return;
        }

        // Non-AJAX: render HTML fallback for normal page loads
        if(empty($conversation)){
            echo '<div style="padding:18px;color:#999">No messages yet.</div>';
        } else {
            foreach($conversation as $m){
                echo '<div style="margin-bottom:12px">';
                echo '<div style="font-weight:600;margin-bottom:6px">'.htmlspecialchars($m['full_name']).' <small style="color:#666">— '.htmlspecialchars($m['date_sent']).'</small></div>';
                echo '<div style="background:'.($m['from_admin'] ? 'rgba(11,116,222,0.12)' : 'rgba(255,255,255,0.06)').';display:inline-block;padding:8px;border-radius:8px;">'.nl2br(htmlspecialchars($m['message'])).'</div>';
                echo '</div>';
            }
        }
    }

    // JSON-only API for admin conversation fetch (always returns JSON)
    public function messages_api()
    {
        // Diagnostic logging: record when this endpoint is hit to help debug AJAX vs session issues
        try{
            $projectRoot = dirname(__DIR__, 2);
            $logDir = $projectRoot . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'logs';
            if(!is_dir($logDir)){ @mkdir($logDir, 0755, true); }
            $entry = "[".date('Y-m-d H:i:s')."] messages_api called\n";
            $entry .= "REMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? 'cli') . "\n";
            $entry .= "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? '') . "\n";
            $entry .= "HTTP_COOKIE: " . ($_SERVER['HTTP_COOKIE'] ?? '') . "\n";
            $entry .= "is_ajax: " . ($this->io->is_ajax() ? '1' : '0') . "\n";
            $entry .= "session_admin: " . ($this->session->has_userdata('admin') ? '1' : '0') . "\n\n";
            @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'admin-messages-api.log', $entry, FILE_APPEND | LOCK_EX);
        } catch(Exception $e) { /* ignore logging errors */ }

        if(!$this->session->has_userdata('admin')){
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'msg' => 'Not authorized']);
            return;
        }

        $this->call->model('MessagesModel');
        $this->call->model('UsersModel');
        $user_id = intval($this->io->get('user_id')) ?: null;
        if(!$user_id){
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'msg' => 'No user selected']);
            return;
        }

        // mark messages from user as read
        try{
            $this->MessagesModel->raw('UPDATE messages SET is_read = 1 WHERE user_id = ? AND from_admin = 0', [$user_id]);
        } catch(Exception $e){ /* ignore if column missing */ }

        try{
            $stmt = $this->MessagesModel->raw('SELECT m.*, u.full_name FROM messages m JOIN users u ON u.user_id = m.user_id WHERE m.user_id = ? ORDER BY m.date_sent ASC', [$user_id]);
            $conversation = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'msg' => 'Database error', 'detail' => $e->getMessage()]);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok', 'conversation' => $conversation]);
        return;
    }
    public function __construct()
    {
        parent::__construct();
        $this->call->model('AdminModel');
        // load models used by dashboard
        $this->call->model('RoomsModel');
        $this->call->model('BookingsModel');
        $this->call->model('UsersModel');
        $this->call->model('PaymentsModel');
    }

    public function index()
    {
        // require admin session (use Session library)
        if(!$this->session->has_userdata('admin')){
            redirect(site_url('admin/login'));
            return;
        }

        // fetch counts from database
        $roomsCount = (int) $this->RoomsModel->count();
        $bookingsCount = (int) $this->BookingsModel->count();
        $usersCount = (int) $this->UsersModel->count();

        // revenue: sum of payments.amount where payment_status = 'approved' OR all payments
        try{
            $stmt = $this->PaymentsModel->raw("SELECT COALESCE(SUM(amount),0) AS total FROM payments WHERE payment_status = 'approved'");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $revenue = isset($row['total']) ? (float) $row['total'] : 0.0;
        } catch(Exception $e) {
            // fallback to zero on error
            $revenue = 0.0;
        }
        // If payments table is not populated (e.g. mock flows) fall back to summing approved/completed bookings
        try{
            if (empty($revenue) || $revenue <= 0){
                $stmt2 = $this->BookingsModel->raw("SELECT COALESCE(SUM(total_amount),0) AS total FROM bookings WHERE status IN ('approved','completed')");
                $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
                $revenue = isset($row2['total']) ? (float) $row2['total'] : $revenue;
            }
        } catch(Exception $e){ /* ignore fallback errors */ }

        // fetch a few recent pending bookings to show on dashboard for quick actions
        try{
            $stmt = $this->BookingsModel->raw("SELECT b.booking_id, b.user_id, b.room_id, b.check_in, b.check_out, b.total_amount, b.status, u.full_name, r.room_number FROM bookings b LEFT JOIN users u ON u.user_id = b.user_id LEFT JOIN rooms r ON r.room_id = b.room_id WHERE b.status = 'pending' ORDER BY b.date_booked ASC LIMIT 6");
            $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e){
            $pending = [];
        }

    // generate a short-lived admin action token stored in session to ensure actions come from the dashboard
    $adminToken = bin2hex(random_bytes(16));
    try{ $this->session->set_userdata('admin_action_token', $adminToken); } catch(Exception $e) { /* ignore session set issues */ }
    // also expose the token via a non-HttpOnly cookie as a fallback so AJAX-loaded fragments can access it
    // cookie lifetime: session cookie (0), path '/'
    try{ @setcookie('admin_action_token', $adminToken, 0, '/'); } catch(Exception $e) { /* ignore cookie set errors */ }

        $data = [
            'rooms' => $roomsCount,
            'bookings' => $bookingsCount,
            'users' => $usersCount,
            'revenue' => $revenue,
            'pending_bookings' => $pending,
            'admin_action_token' => $adminToken
        ];

        // expose current admin display name to the view (avoid direct $_SESSION usage in views)
        $admin = $this->session->userdata('admin');
        $data['admin_name'] = isset($admin['full_name']) ? $admin['full_name'] : 'Admin';

        // If there is a pending request to open a specific admin fragment (e.g. audit),
        // pass a flag to the view and clear it from session. This allows /admin to
        // automatically load that fragment into the dashboard main area without
        // changing the browser URL.
        $initialLoad = $this->session->userdata('admin_initial_load') ?? null;
        if ($initialLoad) {
            $data['initial_load'] = $initialLoad; // e.g. 'audit'
            // clear the session flag so it doesn't repeat
            try { $this->session->unset_userdata('admin_initial_load'); } catch(Exception $e) { /* ignore */ }
        }

        $this->call->view('admin/index', $data);
    }

    public function login()
    {
        // ensure default admin exists
        $defaultEmail = 'admin@admin.admin';
        $defaultPass = 'admin123';

        $existing = $this->AdminModel->findByUsername($defaultEmail);
        if(!$existing){
            $this->AdminModel->insert([
                'username' => $defaultEmail,
                'password' => password_hash($defaultPass, PASSWORD_DEFAULT),
                'full_name' => 'Administrator',
                'role' => 'superadmin'
            ]);
        }

        if($this->io->method() === 'post'){
            $email = $this->io->post('email');
            $password = $this->io->post('password');

            // First: fallback - if user supplied the default credentials exactly,
            // accept them and ensure the admin row exists. This creates a working
            // default admin even if DB verification was failing.
            if ($email === $defaultEmail && $password === $defaultPass) {
                $admin = $this->AdminModel->findByUsername($email);
                if (!$admin) {
                    $newId = $this->AdminModel->insert([
                        'username' => $defaultEmail,
                        'password' => password_hash($defaultPass, PASSWORD_DEFAULT),
                        'full_name' => 'Administrator',
                        'role' => 'superadmin'
                    ]);
                    // fetch the inserted row
                    $admin = $this->AdminModel->findByUsername($email);
                }

                $this->session->set_userdata('admin', [
                    'admin_id' => isset($admin['admin_id']) ? $admin['admin_id'] : null,
                    'username' => $email,
                    'full_name' => isset($admin['full_name']) ? $admin['full_name'] : 'Administrator',
                    'role' => isset($admin['role']) ? $admin['role'] : 'superadmin'
                ]);
                redirect(site_url('admin'));
                return;
            }

            $admin = $this->AdminModel->findByUsername($email);
            $ok = false;
            if($admin && isset($admin['password'])){
                $ok = password_verify($password, $admin['password']);
            }
            if($ok){
                // set admin session only (use Session library)
                $this->session->set_userdata('admin', [
                    'admin_id' => $admin['admin_id'],
                    'username' => $admin['username'],
                    'full_name' => $admin['full_name'],
                    'role' => $admin['role']
                ]);
                redirect(site_url('admin'));
                return;
            }

            // prepare debug info for development (do not expose sensitive info in production)
            $data['error'] = 'Invalid admin credentials';
            $data['debug'] = [
                'admin_found' => (bool)$admin,
                'stored_hash' => isset($admin['password']) ? substr($admin['password'], 0, 20) . '...' : null,
                'password_length' => isset($admin['password']) ? strlen($admin['password']) : 0,
                'verify_with_admin123' => isset($admin['password']) ? (password_verify('admin123', $admin['password']) ? true : false) : false,
                'posted_email' => isset($email) ? $email : null,
                'posted_email_length' => isset($email) ? strlen($email) : 0,
                'posted_password_length' => isset($password) ? strlen($password) : 0,
                'posted_password_md5' => isset($password) ? md5($password) : null
            ];
            $this->call->view('admin/login', $data);
            return;
        }

        $this->call->view('admin/login');
    }

    public function logout()
    {
        $this->session->unset_userdata('admin');
        redirect(site_url(''));
    }



    public function bookings()
    {
        if(!$this->session->has_userdata('admin')){ redirect(site_url('admin/login')); return; }
        $this->call->model('BookingsModel');
        // fetch bookings with guest and room info
    // Exclude bookings that are completed or ended here — those are shown in the audit page
    $sql = "SELECT b.*, u.full_name, r.room_number FROM bookings b
        LEFT JOIN users u ON u.user_id = b.user_id
        LEFT JOIN rooms r ON r.room_id = b.room_id
        WHERE COALESCE(LOWER(b.status),'') NOT IN ('completed','ended')
        ORDER BY b.date_booked DESC";
        $stmt = $this->BookingsModel->raw($sql);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // If this is an AJAX request, return the fragment as before.
        if ($this->io->is_ajax()) {
            $this->call->view('admin/bookings', ['bookings' => $bookings]);
            return;
        }

        // For direct navigation to /admin/bookings, redirect back to /admin and
        // request the dashboard to load the bookings fragment in-place.
        try{
            $this->session->set_userdata('admin_initial_load', 'bookings');
        } catch(Exception $e) { /* ignore session set errors */ }
        redirect(site_url('admin'));
    }

    // Show admin audit logs (recent)
    public function audit()
    {
        if(!$this->session->has_userdata('admin')){ redirect(site_url('admin/login')); return; }
        $this->call->model('AdminAuditModel');
        // fetch recent audits (limit 200)
        try{
            // Fetch all rows directly from the admin_audit table (match SELECT * FROM `admin_audit`)
            $sql = "SELECT * FROM admin_audit ORDER BY date_created DESC";
            $stmt = $this->AdminAuditModel->raw($sql);
            $audits = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e){
            $audits = [];
        }

        // also fetch completed bookings to show alongside audits
        $completed = [];
        try{
            $this->call->model('BookingsModel');
            // Include bookings marked completed OR ended in the audit completed bookings list
            $sql2 = "SELECT b.*, u.full_name, r.room_number FROM bookings b LEFT JOIN users u ON u.user_id = b.user_id LEFT JOIN rooms r ON r.room_id = b.room_id WHERE COALESCE(LOWER(b.status),'') IN ('completed','ended') ORDER BY b.date_booked DESC";
            // Remove LIMIT to fetch all completed/ended bookings
            $stmt2 = $this->BookingsModel->raw($sql2);
            $completed = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e){
            $completed = [];
        }

        // If this is an AJAX request, return the audit fragment as before.
        if ($this->io->is_ajax()) {
            $this->call->view('admin/audit', ['audits' => $audits, 'completed_bookings' => $completed]);
            return;
        }

        // For direct (non-AJAX) requests to /admin/audit, redirect back to /admin
        // and set a session flag so the dashboard will load the audit fragment
        // into the main area without changing the browser URL.
        try{
            $this->session->set_userdata('admin_initial_load', 'audit');
        } catch(Exception $e) { /* ignore session set errors */ }
        redirect(site_url('admin'));
    }

    public function rooms_add()
    {
        if(!$this->session->has_userdata('admin')){ redirect(site_url('admin/login')); return; }
        // handle POST submission
        if($this->io->method() === 'post'){
            $room_number = trim($this->io->post('room_number'));
            $room_type = trim($this->io->post('room_type'));
            $price = $this->io->post('price_per_night');
            $capacity = intval($this->io->post('capacity'));
            $description = trim($this->io->post('description'));

            // basic validation
            if(empty($room_number) || empty($room_type) || !is_numeric($price) || $capacity < 1){
                $data['error'] = 'Please fill required fields and make sure price is numeric.';
                $this->call->view('admin/rooms_add', $data);
                return;
            }

            // normalize room number (trim and uppercase) for comparison when updating

            // handle image upload if provided
            $image_path = NULL;
            if(isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE){
                // Use the autoloaded upload library
                $this->upload->set_dir(PUBLIC_DIR . '/uploads/rooms');
                $this->upload->is_image();
                // restrict size (optional) and types
                $this->upload->allowed_extensions(array('jpg','jpeg','png','gif','bmp','webp','tiff','svg'));
                $this->upload->allowed_mimes(array('image/jpeg','image/png','image/jpg','image/gif','image/bmp','image/webp','image/tiff','image/svg+xml'));
                $this->upload->max_size(5); // 5 MB

                // set the uploaded file array
                $this->upload->file = $_FILES['image'];

                if($this->upload->do_upload(FALSE)){
                    $filename = $this->upload->get_filename();
                    // store relative path under public/uploads/rooms
                    $image_path = 'uploads/rooms/' . $filename;
                } else {
                    $errs = $this->upload->get_errors();
                    $data['error'] = implode('; ', $errs);
                    $this->call->view('admin/rooms_add', $data);
                    return;
                }
            }

            // prepare insert
            $roomData = [
                'room_number' => $room_number,
                'room_type' => $room_type,
                'price_per_night' => number_format((float)$price, 2, '.', ''),
                'capacity' => $capacity,
                'description' => $description,
                'image' => $image_path
            ];

            // Prevent duplicate room_number (there is a unique key in the DB)
            try{
                $stmtCheck = $this->RoomsModel->raw('SELECT room_id FROM rooms WHERE room_number = ? LIMIT 1', [$room_number]);
                $exists = $stmtCheck ? $stmtCheck->fetch(PDO::FETCH_ASSOC) : false;
                if($exists){
                    $data['error'] = 'Room number already exists. Please choose a different room number.';
                    $this->call->view('admin/rooms_add', $data);
                    return;
                }
            } catch(Exception $e){
                // if check fails, log and continue to let DB throw the unique constraint error
                error_log('[AdminController] rooms_add duplicate check failed: ' . $e->getMessage());
            }

            if($this->RoomsModel->insert($roomData)){
                // redirect back to rooms list (or admin dashboard)
                if($this->io->is_ajax()){
                    echo 'OK'; return;
                }
                redirect(site_url('admin/rooms_list'));
                return;
            } else {
                $data['error'] = 'Unable to add room. Please try again.';
                $this->call->view('admin/rooms_add', $data);
                return;
            }
        }

        // AJAX fragment support: return fragment on AJAX, otherwise redirect to /admin
        if ($this->io->is_ajax()){
            $this->call->view('admin/rooms_add');
            return;
        }
        try{ $this->session->set_userdata('admin_initial_load', 'rooms/add'); } catch(Exception $e) { }
        redirect(site_url('admin'));
    }

    public function rooms_list()
    {
        if(!$this->session->has_userdata('admin')){ redirect(site_url('admin/login')); return; }
    // Pagination: read ?page=N
    $page = intval($this->io->get('page')) ?: 1;
    $per_page = 3; // cards per page (changed to 3)
    if($page < 1) $page = 1;
    $offset = ($page - 1) * $per_page;

    // fetch total count and paged rows
    try{
        $total = intval($this->RoomsModel->count());
    } catch(Exception $e){
        $total = 0;
    }
    $last_page = $per_page > 0 ? (int) ceil($total / $per_page) : 1;

    try{
        // Order rooms numerically by room_number so '1' appears first.
        // Use CAST to ensure numeric ordering even if room_number is stored as string.
        $sql = "SELECT * FROM rooms ORDER BY CAST(room_number AS UNSIGNED) ASC, room_number ASC LIMIT ? OFFSET ?";
        $stmt = $this->RoomsModel->raw($sql, [$per_page, $offset]);
        $rooms = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch(Exception $e){
        $rooms = [];
    }

    $viewData = [
        'rooms' => $rooms,
        'per_page' => $per_page,
        'current_page' => $page,
        'last_page' => max(1, $last_page),
        'total' => $total
    ];

        if ($this->io->is_ajax()){
            $this->call->view('admin/rooms_list', $viewData);
            return;
        }
        try{ $this->session->set_userdata('admin_initial_load', 'rooms'); } catch(Exception $e) { }
        redirect(site_url('admin'));
    }

    public function rooms_available()
    {
        if(!$this->session->has_userdata('admin')){ redirect(site_url('admin/login')); return; }
        // show only available rooms
        $rooms = $this->RoomsModel->filter(['status' => 'available'])->get_all();
        if ($this->io->is_ajax()){
            $this->call->view('admin/rooms_available', ['rooms' => $rooms]);
            return;
        }
        try{ $this->session->set_userdata('admin_initial_load', 'rooms/available'); } catch(Exception $e) { }
        redirect(site_url('admin'));
    }

    public function rooms_toggle($id = null)
    {
        if(!$this->session->has_userdata('admin')){ redirect(site_url('admin/login')); return; }
        if(!$id){ echo 'Room id required'; return; }

        $room = $this->RoomsModel->find($id);
        if(!$room){ echo 'Room not found'; return; }

        $new = ($room['status'] === 'available') ? 'booked' : 'available';
        if($this->RoomsModel->update($id, ['status' => $new])){
            if($this->io->is_ajax()){ echo 'OK'; return; }
            redirect(site_url('admin/rooms'));
            return;
        }
        echo 'Unable to change status';
    }

    public function rooms_edit($id = null)
    {
        if(!$this->session->has_userdata('admin')){ redirect(site_url('admin/login')); return; }
        if(!$id){ echo 'Room id required'; return; }
        $room = $this->RoomsModel->find($id);
        if(!$room){ echo 'Room not found'; return; }
        $this->call->view('admin/rooms_edit', ['room' => $room]);
    }

    public function rooms_update($id = null)
    {
        if(!$this->session->has_userdata('admin')){ redirect(site_url('admin/login')); return; }
        if(!$id){ echo 'Room id required'; return; }

        $room = $this->RoomsModel->find($id);
        if(!$room){ echo 'Room not found'; return; }

        if($this->io->method() === 'post'){
            $room_number = trim($this->io->post('room_number'));
            $room_type = trim($this->io->post('room_type'));
            $price = trim(str_replace([',',' '], '', $this->io->post('price_per_night')));
            $capacity = intval($this->io->post('capacity'));
            $description = trim($this->io->post('description'));

            $data = [];
            // If validation fails, repopulate form with submitted values
            $room_new = $room;
            $room_new['room_number'] = $room_number;
            $room_new['room_type'] = $room_type;
            $room_new['price_per_night'] = $price;
            $room_new['capacity'] = $capacity;
            $room_new['description'] = $description;

            if(empty($room_number) || empty($room_type) || !is_numeric($price) || $capacity < 1){
                $data['error'] = 'Please fill required fields and make sure price is numeric.';
                $data['room'] = $room_new;
                $this->call->view('admin/rooms_edit', $data);
                return;
            }

            // Prevent updating room_number to one that already exists for another room
            try{
                $stmtDup = $this->RoomsModel->raw('SELECT room_id FROM rooms WHERE room_number = ? AND room_id != ? LIMIT 1', [$room_number, intval($id)]);
                $dup = $stmtDup ? $stmtDup->fetch(PDO::FETCH_ASSOC) : false;
                if($dup){
                    $data['error'] = 'Room number already used by another room. Please choose a different room number.';
                    $data['room'] = $room_new;
                    $this->call->view('admin/rooms_edit', $data);
                    return;
                }
            } catch(Exception $e){
                error_log('[AdminController] rooms_update duplicate check failed: ' . $e->getMessage());
            }

            $image_path = $room['image'];
            // handle replacing image
            if(isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE){
                $this->upload->set_dir(PUBLIC_DIR . '/uploads/rooms');
                $this->upload->is_image();
                $this->upload->allowed_extensions(array('jpg','jpeg','png','gif','bmp','webp','tiff','svg'));
                $this->upload->allowed_mimes(array('image/jpeg','image/png','image/jpg','image/gif','image/bmp','image/webp','image/tiff','image/svg+xml'));
                $this->upload->max_size(5);
                $this->upload->file = $_FILES['image'];

                if($this->upload->do_upload(FALSE)){
                    $filename = $this->upload->get_filename();
                    $image_path = 'uploads/rooms/' . $filename;
                    // optionally remove old image file (best-effort)
                    if(!empty($room['image'])){
                        $oldPath = PUBLIC_DIR . '/' . $room['image'];
                        if(is_file($oldPath)){
                            @unlink($oldPath);
                        }
                    }
                } else {
                    $errs = $this->upload->get_errors();
                    $data['error'] = implode('; ', $errs);
                    $data['room'] = $room_new;
                    $this->call->view('admin/rooms_edit', $data);
                    return;
                }
            }

            $update = [
                'room_number' => $room_number,
                'room_type' => $room_type,
                'price_per_night' => number_format((float)$price, 2, '.', ''),
                'capacity' => $capacity,
                'description' => $description,
                'image' => $image_path
            ];

            if($this->RoomsModel->update($id, $update)){
                if($this->io->is_ajax()){ echo 'OK'; return; }
                redirect(site_url('admin/rooms'));
                return;
            } else {
                $data['error'] = 'Unable to update room.';
                $data['room'] = $room_new;
                $this->call->view('admin/rooms_edit', $data);
                return;
            }
        }
    }

    public function rooms_delete($id = null)
    {
        if(!$this->session->has_userdata('admin')){ redirect(site_url('admin/login')); return; }
        if(!$id){ echo 'Room id required'; return; }
        $room = $this->RoomsModel->find($id);
        if(!$room){ echo 'Room not found'; return; }

        // attempt delete
            if($this->RoomsModel->delete($id)){
            // remove image file (best-effort, check existence first)
            if(!empty($room['image'])){
                $toDelete = PUBLIC_DIR . '/' . $room['image'];
                if(is_file($toDelete)) {@unlink($toDelete);} 
            }
            if($this->io->is_ajax()){ echo 'OK'; return; }
            redirect(site_url('admin/rooms'));
        } else {
            echo 'Unable to delete room.';
        }
    }

    public function analytics()
    {
        if(!$this->session->has_userdata('admin')){ redirect(site_url('admin/login')); return; }
        // Prepare aggregates for weekly, monthly, yearly incomes using payments table
        $now = new DateTime();

        // Weekly: last 7 days
        $weekly = [];
        for($i = 6; $i >= 0; $i--){
            $d = clone $now; $d->modify('-'.$i.' days');
            $label = $d->format('D');
            $start = $d->format('Y-m-d') . ' 00:00:00';
            $end = $d->format('Y-m-d') . ' 23:59:59';
            $stmt = $this->PaymentsModel->raw("SELECT COALESCE(SUM(amount),0) AS total FROM payments WHERE payment_status = 'approved' AND payment_date BETWEEN ? AND ?", [$start, $end]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $val = (float) ($row['total'] ?? 0);
            // fallback: sum bookings.total_amount if payments empty
            if (empty($val) || $val <= 0){
                try{
                    $s2 = $this->BookingsModel->raw("SELECT COALESCE(SUM(total_amount),0) AS total FROM bookings WHERE status IN ('approved','completed') AND date_booked BETWEEN ? AND ?", [$start, $end]);
                    $r2 = $s2->fetch(PDO::FETCH_ASSOC);
                    $val = (float) ($r2['total'] ?? $val);
                } catch(Exception $e){ /* ignore */ }
            }
            $weekly['labels'][] = $label;
            $weekly['values'][] = $val;
        }

        // Monthly: this year by month
        $monthly = ['labels'=>[], 'values'=>[]];
        $year = intval($now->format('Y'));
        for($m = 1; $m <= 12; $m++){
            $label = DateTime::createFromFormat('!m', $m)->format('M');
            $start = sprintf('%04d-%02d-01 00:00:00', $year, $m);
            $endDay = (new DateTime($start))->format('t');
            $end = sprintf('%04d-%02d-%02d 23:59:59', $year, $m, $endDay);
            $stmt = $this->PaymentsModel->raw("SELECT COALESCE(SUM(amount),0) AS total FROM payments WHERE payment_status = 'approved' AND payment_date BETWEEN ? AND ?", [$start, $end]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $val = (float) ($row['total'] ?? 0);
            if (empty($val) || $val <= 0){
                try{
                    $s2 = $this->BookingsModel->raw("SELECT COALESCE(SUM(total_amount),0) AS total FROM bookings WHERE status IN ('approved','completed') AND date_booked BETWEEN ? AND ?", [$start, $end]);
                    $r2 = $s2->fetch(PDO::FETCH_ASSOC);
                    $val = (float) ($r2['total'] ?? $val);
                } catch(Exception $e){ }
            }
            $monthly['labels'][] = $label;
            $monthly['values'][] = $val;
        }

        // Yearly: last 5 years
        $yearly = ['labels'=>[], 'values'=>[]];
        for($y = $year-4; $y <= $year; $y++){
            $start = sprintf('%04d-01-01 00:00:00', $y);
            $end = sprintf('%04d-12-31 23:59:59', $y);
            $stmt = $this->PaymentsModel->raw("SELECT COALESCE(SUM(amount),0) AS total FROM payments WHERE payment_status = 'approved' AND payment_date BETWEEN ? AND ?", [$start, $end]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $val = (float) ($row['total'] ?? 0);
            if (empty($val) || $val <= 0){
                try{
                    $s2 = $this->BookingsModel->raw("SELECT COALESCE(SUM(total_amount),0) AS total FROM bookings WHERE status IN ('approved','completed') AND date_booked BETWEEN ? AND ?", [$start, $end]);
                    $r2 = $s2->fetch(PDO::FETCH_ASSOC);
                    $val = (float) ($r2['total'] ?? $val);
                } catch(Exception $e){ }
            }
            $yearly['labels'][] = (string)$y;
            $yearly['values'][] = $val;
        }

        $data = [
            'weekly' => $weekly,
            'monthly' => $monthly,
            'yearly' => $yearly,
            'admin_name' => $this->session->userdata('admin')['full_name'] ?? 'Admin'
        ];

        if ($this->io->is_ajax()){
            $this->call->view('admin/analytics', $data);
            return;
        }
        try{ $this->session->set_userdata('admin_initial_load', 'analytics'); } catch(Exception $e) { }
        redirect(site_url('admin'));
    }

    public function messages()
    {
        // If admin session missing, return JSON 401 for AJAX calls to avoid HTML redirects
        if(!$this->session->has_userdata('admin')){
            if($this->io->is_ajax()){
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'msg' => 'Not authorized']);
                return;
            }
            redirect(site_url('admin/login')); return;
        }

        // load models
        $this->call->model('UsersModel');
        $this->call->model('MessagesModel');

        // If POST, create a message (admin -> user)
        if($this->io->method() === 'post'){
            $user_id = intval($this->io->post('user_id'));
            $message = trim($this->io->post('message'));

            if(!$user_id || empty($message)){
                if($this->io->is_ajax()){
                    header('Content-Type: application/json');
                    echo json_encode(['status'=>'error','msg'=>'User and message required']);
                    return;
                }
                echo 'User and message required'; return;
            }

            // ensure user exists
            $user = $this->UsersModel->find($user_id);
            if(!$user){
                if($this->io->is_ajax()){
                    header('Content-Type: application/json');
                    echo json_encode(['status'=>'error','msg'=>'User not found']);
                    return;
                }
                echo 'User not found'; return;
            }

            $insert = [
                'from_admin' => 1,
                'user_id' => $user_id,
                'message' => $message
            ];
            try{
                $this->MessagesModel->insert($insert);
            } catch(Exception $e){
                if($this->io->is_ajax()){
                    http_response_code(500);
                    header('Content-Type: application/json');
                    echo json_encode(['status'=>'error','msg'=>'Unable to save message','detail'=>$e->getMessage()]);
                    return;
                }
                echo 'Unable to save message: '.$e->getMessage();
                return;
            }

            if($this->io->is_ajax()){
                header('Content-Type: application/json');
                echo json_encode(['status'=>'ok']);
                return;
            }
            // simple redirect back to conversation
            redirect(site_url('admin/messages?user_id='.$user_id));
            return;
        }

        // GET: show users list and optionally the conversation for selected user
        $users = [];
        $debugInfo = "AdminController::messages() GET request\n";
        
        // Check if Model is even loaded
        $debugInfo .= "UsersModel loaded? " . (isset($this->UsersModel) ? "YES" : "NO") . "\n";
        $debugInfo .= "UsersModel type: " . gettype($this->UsersModel) . "\n";
        
        if(isset($this->UsersModel) && is_object($this->UsersModel)) {
            $debugInfo .= "UsersModel class: " . get_class($this->UsersModel) . "\n";
            $debugInfo .= "UsersModel has 'db' prop? " . (property_exists($this->UsersModel, 'db') ? "YES" : "NO") . "\n";
            $debugInfo .= "UsersModel has 'table' prop? " . (property_exists($this->UsersModel, 'table') ? "YES" : "NO") . "\n";
        }
        
        // First do a raw query test to see if database is working at all
        try {
            $debugInfo .= "Testing raw database query...\n";
            $stmt = $this->UsersModel->raw("SELECT COUNT(*) as cnt FROM users");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $debugInfo .= "Raw query COUNT result: " . json_encode($row) . "\n";
        } catch(Exception $e) {
            $debugInfo .= "Raw query COUNT failed: " . $e->getMessage() . "\n";
        }
        
        // Try to get all users - start with the simplest possible query
        try {
            $debugInfo .= "Fetching users with unread counts via raw SQL...\n";
            // Fetch users and include unread message count (messages from user to admin that are unread)
            $sql = "SELECT u.*, COALESCE((SELECT COUNT(*) FROM messages m WHERE m.user_id = u.user_id AND m.from_admin = 0 AND m.is_read = 0),0) AS unread FROM users u ORDER BY u.full_name ASC";
            $stmt = $this->UsersModel->raw($sql);
            $users = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            $debugInfo .= "Users fetched: " . count($users) . "\n";
            $debugInfo .= "Users raw: " . json_encode($users) . "\n";
            if(!is_array($users)) { $users = []; }
        } catch(Exception $e) {
            $debugInfo .= "Exception fetching users with unread: " . $e->getMessage() . "\n";
            $users = [];
        }
        
        // Log debug info
        try {
            $projectRoot = dirname(__DIR__, 2);
            $logDir = $projectRoot . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'logs';
            if(!is_dir($logDir)) @mkdir($logDir, 0755, true);
            
            $logContent = "[" . date('Y-m-d H:i:s') . "]\n";
            $logContent .= $debugInfo . "\n";
            $logContent .= "Final users array (JSON): " . json_encode($users) . "\n";
            $logContent .= "is_ajax: " . ($this->io->is_ajax() ? 'YES' : 'NO') . "\n";
            $logContent .= "---\n\n";
            
            @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'messages-debug.log', $logContent, FILE_APPEND | LOCK_EX);
        } catch(Exception $e) { }
        
        
        $selected = intval($this->io->get('user_id')) ?: null;
        $conversation = [];
        if($selected){
            try {
                $stmt = $this->MessagesModel->raw('SELECT m.*, u.full_name FROM messages m JOIN users u ON u.user_id = m.user_id WHERE m.user_id = ? ORDER BY m.date_sent ASC', [$selected]);
                if($stmt) {
                    $conversation = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch(Exception $e) {
                $conversation = [];
            }
        }

        $admin_name = $this->session->userdata('admin')['full_name'] ?? 'Admin';
        
        // return fragment for AJAX; otherwise redirect to /admin and request messages fragment
        if ($this->io->is_ajax()){
            $this->call->view('admin/messages', ['users' => $users, 'conversation' => $conversation, 'selected_user' => $selected, 'admin_name' => $admin_name]);
            return;
        }
        try{ $this->session->set_userdata('admin_initial_load', 'messages'); } catch(Exception $e) { }
        redirect(site_url('admin'));
    }

    /* ==========================
       Admin User Management
       ========================== */
    public function users()
    {
        if(!$this->session->has_userdata('admin')){ redirect(site_url('admin/login')); return; }

        $this->call->model('UsersModel');
        // Fetch all users (old + new) for full list view — no pagination
        try{
            $stmt = $this->UsersModel->raw("SELECT u.* FROM users u ORDER BY u.full_name ASC");
            $users = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            $total = is_array($users) ? count($users) : 0;
        } catch(Exception $e){
            $users = [];
            $total = 0;
        }

        $data = [
            'users' => $users,
            'total' => $total,
            'per_page' => $total,
            'current_page' => 1,
            'last_page' => 1
        ];
        // also provide count of pending verification requests for admin UI
        try{
            $stmt = $this->UsersModel->raw("SELECT COUNT(*) AS cnt FROM users WHERE verification_requested = 1 AND COALESCE(is_verified,0) = 0");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $data['pending_verifications'] = isset($row['cnt']) ? intval($row['cnt']) : 0;
        } catch(Exception $e){
            $data['pending_verifications'] = 0;
        }

        // return fragment for AJAX requests, otherwise redirect to /admin and
        // request the dashboard to load the users fragment in-place.
        if ($this->io->is_ajax()) {
            $this->call->view('admin/users', $data);
            return;
        }
        try{ $this->session->set_userdata('admin_initial_load', 'users'); } catch(Exception $e) { }
        redirect(site_url('admin'));
    }

    public function users_edit($id = null)
    {
        if(!$this->session->has_userdata('admin')){ redirect(site_url('admin/login')); return; }
        if(!$id){ echo 'User id required'; return; }
        $this->call->model('UsersModel');
        $user = $this->UsersModel->find($id);
        if(!$user){ echo 'User not found'; return; }
        $this->call->view('admin/users_edit', ['user' => $user]);
    }

    public function users_update($id = null)
    {
        if(!$this->session->has_userdata('admin')){ redirect(site_url('admin/login')); return; }
        if(!$id){ echo 'User id required'; return; }
        $this->call->model('UsersModel');

        if($this->io->method() == 'post'){
            $full_name = $this->io->post('full_name');
            $email = $this->io->post('email');
            // optional password change
            $password = $this->io->post('password');

            $update = [
                'full_name' => $full_name,
                'email' => $email
            ];
            if(!empty($password)){
                $update['password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            if($this->UsersModel->update($id, $update)){
                // if request is AJAX, return a small fragment
                if($this->io->is_ajax()){
                    echo '<div style="padding:12px;color:#d1ffd1">User updated.</div>';
                    return;
                }
                redirect(site_url('admin/users'));
            }else{
                echo 'Unable to update user.';
            }
        }
    }

    public function users_delete($id = null)
    {
        if(!$this->session->has_userdata('admin')){ redirect(site_url('admin/login')); return; }
        if(!$id){ echo 'User id required'; return; }
        $this->call->model('UsersModel');
        if($this->UsersModel->delete($id)){
            if($this->io->is_ajax()){
                echo 'OK'; return;
            }
            redirect(site_url('admin/users'));
        }else{
            echo 'Error deleting user.';
        }
    }

    // List verification requests for admin review
    public function verification_requests()
    {
        if(!$this->session->has_userdata('admin')){ redirect(site_url('admin/login')); return; }
        $this->call->model('UsersModel');
        $focus_user = intval($this->io->get('user_id')) ?: null;
        try{
            $stmt = $this->UsersModel->raw("SELECT * FROM users WHERE verification_requested = 1 AND COALESCE(is_verified,0) = 0 ORDER BY verification_requested_at DESC");
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e){ $requests = []; }

        if($this->io->is_ajax()){
            $this->call->view('admin/verification_requests', ['requests' => $requests, 'admin_action_token' => $this->session->userdata('admin_action_token'), 'focus_user' => $focus_user]);
            return;
        }
        try{ $this->session->set_userdata('admin_initial_load', 'verification_requests'); } catch(Exception $e) {}
        redirect(site_url('admin'));
    }

    // Approve a verification request
    public function verification_approve($user_id = null)
    {
        if(!$this->session->has_userdata('admin')){ http_response_code(401); echo 'Not authorized'; return; }
        if(!$user_id){ http_response_code(400); echo 'User id required'; return; }
        // verify admin token
        $providedToken = $this->io->post('admin_token') ?: $this->io->get('admin_token');
        if(empty($providedToken) && isset($_COOKIE['admin_action_token'])){ $providedToken = $_COOKIE['admin_action_token']; }
        $sessionToken = $this->session->userdata('admin_action_token') ?? null;
        if(empty($providedToken) || empty($sessionToken) || !hash_equals((string)$sessionToken, (string)$providedToken)){
            http_response_code(403); echo 'Invalid admin action token'; return;
        }
        $this->call->model('UsersModel');
        try{
            $ok = $this->UsersModel->update(intval($user_id), ['is_verified' => 1, 'verification_requested' => 0, 'verification_requested_at' => NULL]);
            if($ok){
                // audit
                try{ $this->call->model('AdminAuditModel'); $admin_id = $this->session->userdata('admin')['admin_id'] ?? null; $this->AdminAuditModel->insert(['admin_id'=>$admin_id,'user_id'=>intval($user_id),'action'=>'verification_approved','note'=>'Approved verification','ip'=>$_SERVER['REMOTE_ADDR'] ?? 'cli']); } catch(Exception $e){}
                // notify user by message and email
                try{
                    $this->call->model('UsersModel');
                    $user = $this->UsersModel->find($user_id);
                    if($user){
                        $msg = 'Your identity verification has been approved. You may now create bookings.';
                        // create message (admin -> user)
                        try{ $this->call->model('MessagesModel'); $this->MessagesModel->insert(['from_admin' => 1, 'user_id' => intval($user_id), 'message' => $msg]); } catch(Exception $e){}
                        // send email if email available
                        if(!empty($user['email'])){
                            try{
                                $this->call->email->to($user['email']);
                                $this->call->email->subject('Verification approved');
                                $body = "Hello " . htmlspecialchars($user['full_name']) . ",<br><br>" . $msg . "<br><br>Thank you,<br>Admin";
                                $this->call->email->message($body);
                                $this->call->email->send();
                            } catch(Exception $e){ /* ignore email errors */ }
                        }
                    }
                } catch(Exception $e){ /* ignore notify errors */ }
            }
            if($this->io->is_ajax()){ header('Content-Type: application/json'); echo json_encode(['status' => $ok ? 'ok' : 'error']); return; }
            redirect(site_url('admin/verification_requests'));
        } catch(Exception $e){ http_response_code(500); echo 'Exception: ' . $e->getMessage(); return; }
    }

    // Reject a verification request
    public function verification_reject($user_id = null)
    {
        if(!$this->session->has_userdata('admin')){ http_response_code(401); echo 'Not authorized'; return; }
        if(!$user_id){ http_response_code(400); echo 'User id required'; return; }
        $providedToken = $this->io->post('admin_token') ?: $this->io->get('admin_token');
        if(empty($providedToken) && isset($_COOKIE['admin_action_token'])){ $providedToken = $_COOKIE['admin_action_token']; }
        $sessionToken = $this->session->userdata('admin_action_token') ?? null;
        if(empty($providedToken) || empty($sessionToken) || !hash_equals((string)$sessionToken, (string)$providedToken)){
            http_response_code(403); echo 'Invalid admin action token'; return;
        }
        $this->call->model('UsersModel');
        $note = trim($this->io->post('note') ?: $this->io->get('note') ?: '');
        try{
            $ok = $this->UsersModel->update(intval($user_id), ['verification_requested' => 0, 'verification_requested_at' => NULL]);
            // audit
            try{ $this->call->model('AdminAuditModel'); $admin_id = $this->session->userdata('admin')['admin_id'] ?? null; $this->AdminAuditModel->insert(['admin_id'=>$admin_id,'user_id'=>intval($user_id),'action'=>'verification_rejected','note'=>$note ?: 'Rejected verification','ip'=>$_SERVER['REMOTE_ADDR'] ?? 'cli']); } catch(Exception $e){}
            // notify user by message and email with optional note
            try{
                $this->call->model('UsersModel');
                $user = $this->UsersModel->find($user_id);
                if($user){
                    $msg = 'Your identity verification request has been rejected.' . (!empty($note) ? '\n\nReason: ' . $note : '');
                    try{ $this->call->model('MessagesModel'); $this->MessagesModel->insert(['from_admin' => 1, 'user_id' => intval($user_id), 'message' => $msg]); } catch(Exception $e){}
                    if(!empty($user['email'])){
                        try{
                            $this->call->email->to($user['email']);
                            $this->call->email->subject('Verification rejected');
                            $body = "Hello " . htmlspecialchars($user['full_name']) . ",<br><br>Your identity verification request has been rejected.";
                            if(!empty($note)) { $body .= "<br><br><strong>Reason:</strong><br>" . nl2br(htmlspecialchars($note)); }
                            $body .= "<br><br>Please update your documents and try again.<br><br>Regards,<br>Admin";
                            $this->call->email->message($body);
                            $this->call->email->send();
                        } catch(Exception $e){ /* ignore email errors */ }
                    }
                }
            } catch(Exception $e){ /* ignore notify errors */ }

            if($this->io->is_ajax()){ header('Content-Type: application/json'); echo json_encode(['status' => $ok ? 'ok' : 'error']); return; }
            redirect(site_url('admin/verification_requests'));
        } catch(Exception $e){ http_response_code(500); echo 'Exception: ' . $e->getMessage(); return; }
    }

    public function users_block($id = null)
    {
        if(!$this->session->has_userdata('admin')){ redirect(site_url('admin/login')); return; }
        if(!$id){ echo 'User id required'; return; }
        $this->call->model('UsersModel');
        $user = $this->UsersModel->find($id);
        if(!$user){ echo 'User not found'; return; }

        $blocked = isset($user['is_blocked']) && $user['is_blocked'] ? 0 : 1;
        // attempt to update the column; if column doesn't exist, update will fail
        if($this->UsersModel->update($id, ['is_blocked' => $blocked])){
            if($this->io->is_ajax()){
                echo 'OK'; return;
            }
            redirect(site_url('admin/users'));
        } else {
            echo 'Unable to update user block status.';
        }
    }

    /* Bookings management: update status */
    public function bookings_update($id = null)
    {
        if(!$this->session->has_userdata('admin')){ redirect(site_url('admin/login')); return; }
        if(!$id){ echo 'Booking id required'; return; }
        // accept status from POST (preferred) or GET
    $status = $this->io->post('status') ?: $this->io->get('status');
    // try to read total_amount from POST/GET first, then fallback to parsing raw input (urlencoded or json)
    $total_amount = $this->io->post('total_amount') !== null ? $this->io->post('total_amount') : $this->io->get('total_amount');
    // If request is GET and admin wants to approve via direct URL, show a simple approval form to collect total
    // Allow status to be provided via GET or POST; do not render a GET form here. State changes must still include admin token.
    if ($total_amount === null) {
        // attempt to parse raw php://input in case PHP didn't populate $_POST (common with some fetch() requests)
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            // try urlencoded parse
            $parsed = [];
            parse_str($raw, $parsed);
            if (isset($parsed['total_amount'])) {
                $total_amount = $parsed['total_amount'];
            } else {
                // maybe JSON body
                $maybeJson = @json_decode($raw, true);
                if (is_array($maybeJson) && isset($maybeJson['total_amount'])) {
                    $total_amount = $maybeJson['total_amount'];
                }
            }
            // If still null, log the raw input for debugging (best-effort)
            if ($total_amount === null) {
                $projectRoot = dirname(__DIR__, 2);
                $logDir = $projectRoot . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'logs';
                if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
                @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'admin-bookings-rawinput.log', "[".date('Y-m-d H:i:s')."] Raw input when expecting total_amount:\n" . $raw . "\n\n", FILE_APPEND | LOCK_EX);
            }
        }
    }
    // require admin action token for dashboard-only operations
    // Accept token from POST/GET or from cookie as a fallback (useful for AJAX fragment loads)
    $providedToken = $this->io->post('admin_token') ?: $this->io->get('admin_token');
    if(empty($providedToken) && isset($_COOKIE['admin_action_token'])){ $providedToken = $_COOKIE['admin_action_token']; }
    $sessionToken = $this->session->userdata('admin_action_token') ?? null;
    if(empty($providedToken) || empty($sessionToken) || !hash_equals((string)$sessionToken, (string)$providedToken)){
        if($this->io->is_ajax()){
            header('Content-Type: application/json'); http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Invalid or missing admin action token. Booking status changes are only allowed from the admin dashboard.']);
            return;
        }
        echo 'Invalid or missing admin action token. Booking status changes are only allowed from the admin dashboard.'; return;
    }
        $allowed = ['pending', 'approved', 'rejected', 'cancelled', 'completed'];
        if(!$status || !in_array($status, $allowed)){
            echo 'Invalid status'; return;
        }
        $this->call->model('BookingsModel');
        // NOTE: previous implementation enforced a referer check; this was removed so admin actions
        // can be performed directly when properly authenticated and possessing a valid admin token.
        try{
            $booking = $this->BookingsModel->find($id);
        } catch(Exception $e){
            $msg = 'Booking lookup failed: ' . $e->getMessage();
            // log
            $projectRoot = dirname(__DIR__, 2);
            $logDir = $projectRoot . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'logs';
            if(!is_dir($logDir)){ @mkdir($logDir,0755,true); }
            @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'admin-bookings-errors.log', "[".date('Y-m-d H:i:s')."] " . $msg . "\nBooking ID: {$id}\nStatus: {$status}\n\n", FILE_APPEND | LOCK_EX);
            if($this->io->is_ajax()){
                header('Content-Type: application/json'); http_response_code(500);
                echo json_encode(['status'=>'error','message'=>'Booking lookup failed','detail'=>$e->getMessage()]); return;
            }
            echo 'Booking lookup failed'; return;
        }

        if(!$booking){
            if($this->io->is_ajax()){
                header('Content-Type: application/json'); http_response_code(404);
                echo json_encode(['status'=>'error','message'=>'Booking not found']); return;
            }
            echo 'Booking not found'; return;
        }

    // If admin explicitly ends/completes a booking, update status to 'completed' (do NOT delete)
        if(strtolower($status) === 'completed'){
            try{
                $okComplete = $this->BookingsModel->update($id, ['status' => 'completed']);
            } catch(Exception $e){
                $projectRoot = dirname(__DIR__, 2);
                $logDir = $projectRoot . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'logs';
                if(!is_dir($logDir)){ @mkdir($logDir,0755,true); }
                $entry = "[".date('Y-m-d H:i:s')."] Booking complete exception\n";
                $entry .= "Booking ID: {$id}\nStatus: {$status}\nException: " . $e->getMessage() . "\n\n";
                @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'admin-bookings-errors.log', $entry, FILE_APPEND | LOCK_EX);
                if($this->io->is_ajax()){ header('Content-Type: application/json'); http_response_code(500); echo json_encode(['status'=>'error','message'=>'Exception completing booking','detail'=>$e->getMessage()]); return; }
                echo 'Exception completing booking'; return;
            }

            if($okComplete){
                // audit
                try{
                    $this->call->model('AdminAuditModel');
                    $admin_id = $this->session->userdata('admin')['admin_id'] ?? null;
                    $user_id = isset($booking['user_id']) ? intval($booking['user_id']) : null;
                    $this->AdminAuditModel->insert([
                        'admin_id' => $admin_id,
                        'booking_id' => intval($id),
                        'user_id' => $user_id,
                        'action' => 'completed',
                        'note' => 'Booking marked as completed/ended',
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'cli'
                    ]);
                } catch(Exception $e){ /* ignore audit failures */ }

                // attempt to free room
                try {
                    if (!empty($booking['room_id'])) {
                        $this->RoomsModel->update(intval($booking['room_id']), ['status' => 'available']);
                    }
                } catch (Exception $e) { /* log previously handled by surrounding code path */ }

                if($this->io->is_ajax()){ header('Content-Type: application/json'); echo json_encode(['status' => 'ok', 'booking_id' => intval($id), 'action' => 'updated', 'new_status' => 'completed']); return; }
                redirect(site_url('admin/bookings'));
            }
            // fallthrough to error handling below
        }

    // If admin declines/rejects a booking, delete the booking request from DB
        if(strtolower($status) === 'rejected'){
            try{
                $deleted = $this->BookingsModel->delete($id);
            } catch(Exception $e){
                $projectRoot = dirname(__DIR__, 2);
                $logDir = $projectRoot . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'logs';
                if(!is_dir($logDir)){ @mkdir($logDir,0755,true); }
                $entry = "[".date('Y-m-d H:i:s')."] Booking delete exception\n";
                $entry .= "Booking ID: {$id}\nStatus: {$status}\nException: " . $e->getMessage() . "\n\n";
                @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'admin-bookings-errors.log', $entry, FILE_APPEND | LOCK_EX);
                if($this->io->is_ajax()){
                    header('Content-Type: application/json'); http_response_code(500);
                    echo json_encode(['status'=>'error','message'=>'Exception deleting booking','detail'=>$e->getMessage()]); return;
                }
                echo 'Exception deleting booking'; return;
            }

            if($deleted){
                // record audit
                try{
                    $this->call->model('AdminAuditModel');
                    $admin_id = $this->session->userdata('admin')['admin_id'] ?? null;
                    $user_id = isset($booking['user_id']) ? intval($booking['user_id']) : null;
                    $this->AdminAuditModel->insert([
                        'admin_id' => $admin_id,
                        'booking_id' => intval($id),
                        'user_id' => $user_id,
                        'action' => 'deleted',
                        'note' => 'Booking rejected and deleted by admin',
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'cli'
                    ]);
                } catch(Exception $e){ /* best-effort audit */ }

                if($this->io->is_ajax()){
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'ok', 'booking_id' => intval($id), 'action' => 'deleted']);
                    return;
                }
                redirect(site_url('admin/bookings'));
            } else {
                // delete returned false
                $projectRoot = dirname(__DIR__, 2);
                $logDir = $projectRoot . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'logs';
                if(!is_dir($logDir)){ @mkdir($logDir,0755,true); }
                $entry = "[".date('Y-m-d H:i:s')."] Booking delete failed (no exception)\n";
                $entry .= "Booking ID: {$id}\nStatus: {$status}\n\n";
                @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'admin-bookings-errors.log', $entry, FILE_APPEND | LOCK_EX);
                if($this->io->is_ajax()){
                    header('Content-Type: application/json'); http_response_code(500);
                    echo json_encode(['status'=>'error','message'=>'Unable to delete booking']); return;
                }
                echo 'Unable to delete booking'; return;
            }
        }

        // For other statuses (approved, pending, cancelled, completed) perform an update
        try{
            // If approving, require a valid total_amount provided by admin
            $updateData = ['status' => $status];
            if(strtolower($status) === 'approved'){
                // total_amount is now optional: if provided, sanitize and set it; otherwise leave existing total_amount untouched
                if($total_amount !== null && trim((string)$total_amount) !== ''){
                    $amount = str_replace([',',' ', '$', '₱', '€', '£'], '', (string)$total_amount);
                    if(is_numeric($amount) && floatval($amount) > 0){
                        $formatted = number_format((float)$amount, 2, '.', '');
                        $updateData['total_amount'] = $formatted;
                    } else {
                        // if provided but invalid, return error
                        if($this->io->is_ajax()){ header('Content-Type: application/json'); http_response_code(400); echo json_encode(['status'=>'error','message'=>'Invalid total amount']); return; }
                        echo 'Invalid total amount'; return;
                    }
                }
            }
            $ok = $this->BookingsModel->update($id, $updateData);
        } catch(Exception $e){
            // log exception
            $projectRoot = dirname(__DIR__, 2);
            $logDir = $projectRoot . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'logs';
            if(!is_dir($logDir)){ @mkdir($logDir,0755,true); }
            $entry = "[".date('Y-m-d H:i:s')."] Booking update exception\n";
            $entry .= "Booking ID: {$id}\nStatus: {$status}\nException: " . $e->getMessage() . "\n\n";
            @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'admin-bookings-errors.log', $entry, FILE_APPEND | LOCK_EX);
            if($this->io->is_ajax()){
                header('Content-Type: application/json'); http_response_code(500);
                echo json_encode(['status'=>'error','message'=>'Exception updating booking','detail'=>$e->getMessage()]); return;
            }
            echo 'Exception updating booking'; return;
        }

        if($ok){
            // record audit for updates
            try{
                $this->call->model('AdminAuditModel');
                $admin_id = $this->session->userdata('admin')['admin_id'] ?? null;
                $user_id = isset($booking['user_id']) ? intval($booking['user_id']) : null;
                // Use a clearer audit action when booking is completed/ended
                $auditAction = (strtolower($status) === 'completed') ? 'completed' : 'updated';
                $note = ($auditAction === 'completed') ? 'Booking marked as completed/ended' : 'Status changed to ' . $status;
                $this->AdminAuditModel->insert([
                    'admin_id' => $admin_id,
                    'booking_id' => intval($id),
                    'user_id' => $user_id,
                    'action' => $auditAction,
                    'note' => $note,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'cli'
                ]);
            } catch(Exception $e){ /* ignore audit failures */ }
            // If booking was completed/ended, attempt to free up the associated room
            if (strtolower($status) === 'completed') {
                try {
                    if (!empty($booking['room_id'])) {
                        $this->RoomsModel->update(intval($booking['room_id']), ['status' => 'available']);
                    }
                } catch (Exception $e) {
                    // best-effort: log error to admin bookings errors
                    $projectRoot = dirname(__DIR__, 2);
                    $logDir = $projectRoot . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'logs';
                    if(!is_dir($logDir)){ @mkdir($logDir,0755,true); }
                    $entry = "[".date('Y-m-d H:i:s')."] Failed to free room after completing booking\n";
                    $entry .= "Booking ID: {$id}\nRoom ID: " . (isset($booking['room_id']) ? intval($booking['room_id']) : 'unknown') . "\nException: " . $e->getMessage() . "\n\n";
                    @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'admin-bookings-errors.log', $entry, FILE_APPEND | LOCK_EX);
                }
            }

            if($this->io->is_ajax()){
                header('Content-Type: application/json');
                echo json_encode(['status' => 'ok', 'booking_id' => intval($id), 'action' => 'updated', 'new_status' => $status]);
                return;
            }
            redirect(site_url('admin/bookings'));
        }

        // fallback: update returned false without exception
        $projectRoot = dirname(__DIR__, 2);
        $logDir = $projectRoot . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'logs';
        if(!is_dir($logDir)){ @mkdir($logDir,0755,true); }
        $entry = "[".date('Y-m-d H:i:s')."] Booking update failed (no exception)\n";
        $entry .= "Booking ID: {$id}\nStatus: {$status}\n\n";
        @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'admin-bookings-errors.log', $entry, FILE_APPEND | LOCK_EX);
        if($this->io->is_ajax()){
            header('Content-Type: application/json'); http_response_code(500);
            echo json_encode(['status'=>'error','message'=>'Unable to update booking (no exception)']); return;
        }
        echo 'Unable to update booking'; return;
    }
}
