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

    /**
     * Reload the user's row from DB and update session so UI reflects persisted values.
     * Returns the fresh user array on success or false on failure.
     */
    protected function refreshUserSession($user_id)
    {
        try{
            $this->call->model('UsersModel');
            $fresh = $this->UsersModel->find(intval($user_id));
            if(!empty($fresh) && is_array($fresh)){
                $this->session->set_userdata('user', $fresh);
                return $fresh;
            }
        } catch(Exception $e){ /* ignore */ }
        return false;
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
        // Fetch rooms ordered numerically by room_number so room '1' appears first
        try{
            $stmt = $this->RoomsModel->raw('SELECT * FROM rooms ORDER BY CAST(room_number AS UNSIGNED) ASC, room_number ASC');
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e){
            // Fallback to existing convenience method if raw query fails for any reason
            try{ $rooms = $this->RoomsModel->All(); } catch(Exception $ie) { $rooms = []; }
        }
        // annotate rooms with booking state: available | pending | booked
        $today = date('Y-m-d');
        if(!empty($rooms)){
            foreach($rooms as &$r){
                $r['booking_state'] = 'available';
                try{
                    // fetch any approved or pending bookings for this room
                    $sql = 'SELECT status, check_in, check_out FROM bookings WHERE room_id = ? AND status IN (?, ?) ORDER BY check_in ASC, check_out ASC';
                    $stmt = $this->BookingsModel->raw($sql, [intval($r['room_id']), 'approved', 'pending']);
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if(!empty($rows)){
                        // mark as currently occupied (booked) only when today is within an approved booking
                        foreach($rows as $row){
                            $st = strtolower($row['status']);
                            $ci = isset($row['check_in']) ? $row['check_in'] : null;
                            $co = isset($row['check_out']) ? $row['check_out'] : null;
                            if($st === 'approved' && $ci && $co){
                                if($ci <= $today && $co >= $today){
                                    $r['booking_state'] = 'booked';
                                    break; // currently occupied
                                }
                            }
                        }
                        // if not currently occupied but there are pending bookings, mark as pending visually
                        if($r['booking_state'] !== 'booked'){
                            foreach($rows as $row){
                                if(strtolower($row['status']) === 'pending'){
                                    $r['booking_state'] = 'pending';
                                    break;
                                }
                            }
                        }
                    }
                }catch(Exception $e){
                    // ignore DB errors here; default to available
                }
            }
            unset($r);
        }

        // If a specific room was requested via ?room_id=, render the room view fragment
        $roomFragment = null;
        try{
            $requestedRoom = intval($this->io->get('room_id')) ?: null;
        } catch(Exception $e){
            $requestedRoom = isset($_GET['room_id']) ? intval($_GET['room_id']) : null;
        }
        if($requestedRoom){
            try{
                $room = $this->RoomsModel->find($requestedRoom);
                if($room){
                    ob_start();
                    $this->call->view('rooms/view', ['room' => $room, 'flashes' => (function_exists('flash_get') ? flash_get() : [])]);
                    $roomFragment = ob_get_clean();
                }
            } catch(Exception $e){ /* ignore render failures */ }
        }

        $this->call->view('user/index', ['user' => $user, 'rooms' => $rooms, 'conversation' => $conversation, 'room_fragment' => $roomFragment]);
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
        // If the profile form was submitted on this same page, delegate to edit_profile()
        try{
            if($this->io->method() === 'post'){
                $this->edit_profile();
                return;
            }
        } catch(Exception $e) { /* if io unavailable, continue to render view */ }

        $this->call->view('user/profile', ['user' => $user]);
    }

    public function edit_profile()
    {
        if(!$this->session->has_userdata('user')){ redirect(site_url('auth/login')); return; }
        $user = $this->session->userdata('user');
        $this->call->model('UsersModel');

        // Detect AJAX callers so we can return JSON instead of an HTML view
        $isAjax = false;
        try{
            $isAjax = $this->io->is_ajax();
        } catch(Exception $e) { $isAjax = false; }

        if($this->io->method() === 'post'){
            // If a cover photo file was submitted, handle cover upload separately
            if(isset($_FILES['cover_photo']) && ($_FILES['cover_photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE){
                // ensure upload directory exists and is writable
                $coverDir = PUBLIC_DIR . '/uploads/covers';
                if(!is_dir($coverDir)){
                    try{ @mkdir($coverDir, 0755, true); } catch(Exception $e){ /* best-effort */ }
                }
                try{
                    // configure upload for cover photos
                    $this->upload->set_dir(PUBLIC_DIR . '/uploads/covers');
                    $this->upload->is_image();
                    $this->upload->allowed_extensions(array('jpg','jpeg','png','webp'));
                    $this->upload->allowed_mimes(array('image/jpeg','image/png','image/webp'));
                    $this->upload->max_size(5); // 5 MB
                    $this->upload->file = $_FILES['cover_photo'];

                    if($this->upload->do_upload(FALSE)){
                        $filename = $this->upload->get_filename();
                        $cover_path = 'uploads/covers/' . $filename;
                        $ok = false;
                        try{
                            $ok = $this->UsersModel->update(intval($user['user_id']), ['cover_photo' => $cover_path]);
                        } catch(Exception $e){
                            $logDir = dirname(__DIR__,2) . '/runtime/logs'; if(!is_dir($logDir)) @mkdir($logDir, 0755, true);
                            @file_put_contents($logDir . '/profile.log', "[".date('c')."] Cover DB update failed: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
                        }
                        // reload session from DB so it reflects persisted values
                        $fresh = $this->refreshUserSession($user['user_id']);
                        if($fresh !== false){ $user = $fresh; }
                        $data = ['user' => $user, 'success' => 'Cover photo updated successfully.'];
                        if(!empty($isAjax)){
                            header('Content-Type: application/json');
                            echo json_encode(['status' => 'ok', 'data' => $data]);
                            return;
                        }
                        $this->call->view('user/profile', $data);
                        return;
                    } else {
                        $errs = $this->upload->get_errors();
                        $data = ['user' => $user, 'error' => implode('; ', $errs)];
                        if(!empty($isAjax)){
                            header('Content-Type: application/json');
                            echo json_encode(['status' => 'error', 'data' => $data]);
                            return;
                        }
                        $this->call->view('user/edit_profile', $data);
                        return;
                    }
                } catch(Exception $e){
                    $logDir = dirname(__DIR__,2) . '/runtime/logs'; if(!is_dir($logDir)) @mkdir($logDir, 0755, true);
                    @file_put_contents($logDir . '/profile.log', "[".date('c')."] Cover upload exception: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
                    $data = ['user' => $user, 'error' => 'Unable to upload cover photo.'];
                    $this->call->view('user/edit_profile', $data);
                    return;
                }
            }

            // If an avatar file was submitted, handle avatar upload separately
            if(isset($_FILES['avatar']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE){
                // Log incoming upload metadata to help diagnose camera/fetch issues
                try{
                    $logDir = dirname(__DIR__,2) . '/runtime/logs'; if(!is_dir($logDir)) @mkdir($logDir, 0755, true);
                    $meta = [];
                    if(isset($_FILES['avatar'])){
                        $f = $_FILES['avatar'];
                        $meta = [
                            'name' => $f['name'] ?? null,
                            'type' => $f['type'] ?? null,
                            'size' => $f['size'] ?? null,
                            'error' => $f['error'] ?? null,
                            'tmp_name' => isset($f['tmp_name']) ? basename($f['tmp_name']) : null
                        ];
                    }
                    $entry = "[".date('c')."] Avatar upload attempt for user_id=".intval($user['user_id'])."\n";
                    $entry .= "REMOTE_ADDR=" . ($_SERVER['REMOTE_ADDR'] ?? 'cli') . " REQUEST_URI=" . ($_SERVER['REQUEST_URI'] ?? '') . "\n";
                    $entry .= "FILES_META=" . json_encode($meta) . "\n";
                    @file_put_contents($logDir . '/profile.log', $entry, FILE_APPEND | LOCK_EX);
                } catch(Exception $e){ /* ignore logging errors */ }
                // ensure upload directory exists and is writable
                $uploadDir = PUBLIC_DIR . '/uploads/avatars';
                if(!is_dir($uploadDir)){
                    try{ @mkdir($uploadDir, 0755, true); } catch(Exception $e){ /* best-effort */ }
                }
                try{
                    // configure upload for avatars
                    $this->upload->set_dir(PUBLIC_DIR . '/uploads/avatars');
                    $this->upload->is_image();
                    $this->upload->allowed_extensions(array('jpg','jpeg','png','gif','webp'));
                    $this->upload->allowed_mimes(array('image/jpeg','image/png','image/jpg','image/gif','image/webp'));
                    $this->upload->max_size(3); // 3 MB
                    $this->upload->file = $_FILES['avatar'];

                    if($this->upload->do_upload(FALSE)){
                        $filename = $this->upload->get_filename();
                        // Generate a unique filename to avoid collisions and cache issues
                        $ext = pathinfo($filename, PATHINFO_EXTENSION);
                        try{
                            $random = bin2hex(random_bytes(6));
                        } catch(Exception $e){
                            $random = uniqid();
                        }
                        $uniqueName = time() . '_' . $random . (!empty($ext) ? '.' . $ext : '');
                        $uploadDir = PUBLIC_DIR . '/uploads/avatars';
                        $oldPath = $uploadDir . '/' . $filename;
                        $newPath = $uploadDir . '/' . $uniqueName;
                        // Attempt to rename the uploaded file to the unique name
                        if(is_file($oldPath)){
                            try{
                                if(!@rename($oldPath, $newPath)){
                                    // fallback to copy+unlink if rename fails
                                    if(@copy($oldPath, $newPath)) {@unlink($oldPath);} else { /* ignore */ }
                                }
                                $filename = $uniqueName;
                            } catch(Exception $e){
                                // keep original filename on error
                            }
                        }
                        $avatar_path = 'uploads/avatars/' . $filename;
                        $ok = false;
                        // log post-upload details to help debugging
                        try{
                            $logDir = dirname(__DIR__,2) . '/runtime/logs'; if(!is_dir($logDir)) @mkdir($logDir, 0755, true);
                            $fullPath = PUBLIC_DIR . '/' . $avatar_path;
                            $exists = is_file($fullPath);
                            $size = $exists ? filesize($fullPath) : null;
                            $entry = "[".date('c')."] Avatar uploaded for user_id=".intval($user['user_id'])."\n";
                            $entry .= "filename=" . $filename . " avatar_path=" . $avatar_path . " full_path=" . $fullPath . " file_exists=" . ($exists ? '1' : '0') . " filesize=" . ($size ?? 'null') . "\n";
                            @file_put_contents($logDir . '/profile.log', $entry, FILE_APPEND | LOCK_EX);
                        } catch(Exception $e){ /* ignore logging errors */ }
                        try{
                            $ok = $this->UsersModel->update(intval($user['user_id']), ['avatar' => $avatar_path]);
                            // log success
                            try{ @file_put_contents(dirname(__DIR__,2) . '/runtime/logs/profile.log', "[".date('c')."] Avatar DB update ok for user_id=".intval($user['user_id'])." -> " . $avatar_path . "\n", FILE_APPEND | LOCK_EX); }catch(Exception $e){}
                        } catch(Exception $e){
                            // best-effort: log but continue to update session so UI reflects change
                            $logDir = dirname(__DIR__,2) . '/runtime/logs'; if(!is_dir($logDir)) @mkdir($logDir, 0755, true);
                            @file_put_contents($logDir . '/profile.log', "[".date('c')."] Avatar DB update failed: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
                        }
                        // reload authoritative user data from DB and update session
                        $fresh = $this->refreshUserSession($user['user_id']);
                        if($fresh !== false){ $user = $fresh; }
                        $data = ['user' => $user, 'success' => 'Avatar updated successfully.'];
                        if(!empty($isAjax)){
                            header('Content-Type: application/json');
                            echo json_encode(['status' => 'ok', 'data' => $data]);
                            return;
                        }
                        $this->call->view('user/profile', $data);
                        return;
                    } else {
                        $errs = $this->upload->get_errors();
                        $data = ['user' => $user, 'error' => implode('; ', $errs)];
                        if(!empty($isAjax)){
                            header('Content-Type: application/json');
                            echo json_encode(['status' => 'error', 'data' => $data]);
                            return;
                        }
                        $this->call->view('user/edit_profile', $data);
                        return;
                    }
                } catch(Exception $e){
                    $logDir = dirname(__DIR__,2) . '/runtime/logs'; if(!is_dir($logDir)) @mkdir($logDir, 0755, true);
                    @file_put_contents($logDir . '/profile.log', "[".date('c')."] Avatar upload exception: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
                    $data = ['user' => $user, 'error' => 'Unable to upload avatar.'];
                    if(!empty($isAjax)){
                        header('Content-Type: application/json');
                        echo json_encode(['status' => 'error', 'data' => $data]);
                        return;
                    }
                    $this->call->view('user/edit_profile', $data);
                    return;
                }
            }

            // If an ID document was submitted (front/back or single file)
            if(isset($_FILES['id_document']) && ($_FILES['id_document']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE){
                $uploadDir = PUBLIC_DIR . '/uploads/ids';
                if(!is_dir($uploadDir)){ try{ @mkdir($uploadDir, 0755, true); } catch(Exception $e){} }
                try{
                    $this->upload->set_dir($uploadDir);
                    $this->upload->is_image();
                    $this->upload->allowed_extensions(array('jpg','jpeg','png','pdf'));
                    $this->upload->allowed_mimes(array('image/jpeg','image/png','application/pdf'));
                    $this->upload->max_size(8); // 8 MB
                    $this->upload->file = $_FILES['id_document'];
                    if($this->upload->do_upload(FALSE)){
                        $filename = $this->upload->get_filename();
                        $ext = pathinfo($filename, PATHINFO_EXTENSION);
                        try{ $random = bin2hex(random_bytes(6)); } catch(Exception $e){ $random = uniqid(); }
                        $uniqueName = time() . '_id_' . $random . (!empty($ext) ? '.' . $ext : '');
                        $oldPath = $uploadDir . '/' . $filename; $newPath = $uploadDir . '/' . $uniqueName;
                        if(is_file($oldPath)){
                            if(!@rename($oldPath, $newPath)) { if(@copy($oldPath, $newPath)) {@unlink($oldPath);} }
                            $filename = $uniqueName;
                        }
                        $id_path = 'uploads/ids/' . $filename;
                        try{ $this->UsersModel->update(intval($user['user_id']), ['id_document' => $id_path]); } catch(Exception $e){ @file_put_contents(dirname(__DIR__,2) . '/runtime/logs/profile.log', "[".date('c')."] ID document DB update failed: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX); }
                        // reload session user from DB after id document saved
                        $user['id_document'] = $id_path;
                        $fresh = $this->refreshUserSession($user['user_id']);
                        if($fresh !== false){ $user = $fresh; }
                        // After upload, if address + id + selfie present, request verification
                        try{
                            $hasAddress = !empty($user['address']);
                            $hasIdDoc = !empty($user['id_document']);
                            $hasSelfie = !empty($user['id_selfie']);
                            $alreadyRequested = !empty($user['verification_requested']);
                            $alreadyVerified = !empty($user['is_verified']);
                            if($hasAddress && $hasIdDoc && $hasSelfie && !$alreadyRequested && !$alreadyVerified){
                                $now = date('Y-m-d H:i:s');
                                try{ $this->UsersModel->update(intval($user['user_id']), ['verification_requested' => 1, 'verification_requested_at' => $now]); }catch(Exception $e){ @file_put_contents(dirname(__DIR__,2) . '/runtime/logs/profile.log', "[".date('c')."] Verification request DB update failed: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX); }
                                $user['verification_requested'] = 1; $user['verification_requested_at'] = $now; 
                                $fresh = $this->refreshUserSession($user['user_id']);
                                if($fresh !== false){ $user = $fresh; }
                                $data = ['user' => $user, 'success' => 'ID document uploaded. Verification requested: an admin will review your documents shortly.'];
                                if(!empty($isAjax)){
                                    header('Content-Type: application/json');
                                    echo json_encode(['status' => 'ok', 'data' => $data]);
                                    return;
                                }
                                $this->call->view('user/profile', $data); return;
                            }
                        } catch(Exception $e){ /* ignore */ }
                        $fresh = $this->refreshUserSession($user['user_id']);
                        if($fresh !== false){ $user = $fresh; }
                        $data = ['user' => $user, 'success' => 'ID document uploaded.'];
                        if(!empty($isAjax)){
                            header('Content-Type: application/json');
                            echo json_encode(['status' => 'ok', 'data' => $data]);
                            return;
                        }
                        $this->call->view('user/profile', $data); return;
                    } else {
                        $errs = $this->upload->get_errors();
                        $data = ['user' => $user, 'error' => implode('; ', $errs)];
                        if(!empty($isAjax)){
                            header('Content-Type: application/json');
                            echo json_encode(['status' => 'error', 'data' => $data]);
                            return;
                        }
                        $this->call->view('user/edit_profile', $data); return;
                    }
                } catch(Exception $e){
                    @file_put_contents(dirname(__DIR__,2) . '/runtime/logs/profile.log', "[".date('c')."] ID upload exception: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
                    $data = ['user'=>$user,'error'=>'Unable to upload ID document'];
                    if(!empty($isAjax)){
                        header('Content-Type: application/json');
                        echo json_encode(['status' => 'error', 'data' => $data]);
                        return;
                    }
                    $this->call->view('user/edit_profile',$data);
                    return;
                }
            }

            // If a selfie (ID selfie) was submitted
            if(isset($_FILES['id_selfie']) && ($_FILES['id_selfie']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE){
                $uploadDir = PUBLIC_DIR . '/uploads/selfies';
                if(!is_dir($uploadDir)){ try{ @mkdir($uploadDir, 0755, true); } catch(Exception $e){} }
                try{
                    $this->upload->set_dir($uploadDir);
                    $this->upload->is_image();
                    $this->upload->allowed_extensions(array('jpg','jpeg','png','webp'));
                    $this->upload->allowed_mimes(array('image/jpeg','image/png','image/webp'));
                    $this->upload->max_size(5); // 5 MB
                    $this->upload->file = $_FILES['id_selfie'];
                    if($this->upload->do_upload(FALSE)){
                        $filename = $this->upload->get_filename();
                        $ext = pathinfo($filename, PATHINFO_EXTENSION);
                        try{ $random = bin2hex(random_bytes(6)); } catch(Exception $e){ $random = uniqid(); }
                        $uniqueName = time() . '_selfie_' . $random . (!empty($ext) ? '.' . $ext : '');
                        $oldPath = $uploadDir . '/' . $filename; $newPath = $uploadDir . '/' . $uniqueName;
                        if(is_file($oldPath)){
                            if(!@rename($oldPath, $newPath)) { if(@copy($oldPath, $newPath)) {@unlink($oldPath);} }
                            $filename = $uniqueName;
                        }
                        $selfie_path = 'uploads/selfies/' . $filename;
                        try{ $this->UsersModel->update(intval($user['user_id']), ['id_selfie' => $selfie_path]); } catch(Exception $e){ @file_put_contents(dirname(__DIR__,2) . '/runtime/logs/profile.log', "[".date('c')."] Selfie DB update failed: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX); }
                        // refresh session user after selfie saved
                        $user['id_selfie'] = $selfie_path;
                        $fresh = $this->refreshUserSession($user['user_id']);
                        if($fresh !== false){ $user = $fresh; }
                        // After selfie upload, check verification conditions
                        try{
                            $hasAddress = !empty($user['address']);
                            $hasIdDoc = !empty($user['id_document']);
                            $hasSelfie = !empty($user['id_selfie']);
                            $alreadyRequested = !empty($user['verification_requested']);
                            $alreadyVerified = !empty($user['is_verified']);
                            if($hasAddress && $hasIdDoc && $hasSelfie && !$alreadyRequested && !$alreadyVerified){
                                $now = date('Y-m-d H:i:s');
                                try{ $this->UsersModel->update(intval($user['user_id']), ['verification_requested' => 1, 'verification_requested_at' => $now]); }catch(Exception $e){ @file_put_contents(dirname(__DIR__,2) . '/runtime/logs/profile.log', "[".date('c')."] Verification request DB update failed: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX); }
                                $user['verification_requested'] = 1; $user['verification_requested_at'] = $now;
                                $fresh = $this->refreshUserSession($user['user_id']);
                                if($fresh !== false){ $user = $fresh; }
                                $data = ['user' => $user, 'success' => 'Selfie uploaded. Verification requested: an admin will review your documents shortly.'];
                                if(!empty($isAjax)){
                                    header('Content-Type: application/json');
                                    echo json_encode(['status' => 'ok', 'data' => $data]);
                                    return;
                                }
                                $this->call->view('user/profile', $data); return;
                            }
                        } catch(Exception $e){ /* ignore */ }
                        $fresh = $this->refreshUserSession($user['user_id']);
                        if($fresh !== false){ $user = $fresh; }
                        $data = ['user' => $user, 'success' => 'Selfie uploaded.'];
                        if(!empty($isAjax)){
                            header('Content-Type: application/json');
                            echo json_encode(['status' => 'ok', 'data' => $data]);
                            return;
                        }
                        $this->call->view('user/profile', $data); return;
                    } else {
                        $errs = $this->upload->get_errors();
                        $data = ['user' => $user, 'error' => implode('; ', $errs)];
                        if(!empty($isAjax)){
                            header('Content-Type: application/json');
                            echo json_encode(['status' => 'error', 'data' => $data]);
                            return;
                        }
                        $this->call->view('user/edit_profile', $data); return;
                    }
                } catch(Exception $e){
                    @file_put_contents(dirname(__DIR__,2) . '/runtime/logs/profile.log', "[".date('c')."] Selfie upload exception: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
                    $data = ['user'=>$user,'error'=>'Unable to upload selfie'];
                    if(!empty($isAjax)){
                        header('Content-Type: application/json');
                        echo json_encode(['status' => 'error', 'data' => $data]);
                        return;
                    }
                    $this->call->view('user/edit_profile',$data);
                    return;
                }
            }

            // If remove avatar requested
            if($this->io->post('remove_avatar')){
                $current = $user['avatar'] ?? null;
                try{
                    if(!empty($current)){@unlink(PUBLIC_DIR . '/' . $current);} 
                } catch(Exception $e){ /* ignore unlink errors */ }
                try{
                    $this->UsersModel->update(intval($user['user_id']), ['avatar' => NULL]);
                } catch(Exception $e){
                    // log but continue
                    $logDir = dirname(__DIR__,2) . '/runtime/logs'; if(!is_dir($logDir)) @mkdir($logDir, 0755, true);
                    @file_put_contents($logDir . '/profile.log', "[".date('c')."] Avatar remove DB update failed: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
                }
                unset($user['avatar']);
                $fresh = $this->refreshUserSession($user['user_id']);
                if($fresh !== false){ $user = $fresh; }
                $data = ['user' => $user, 'success' => 'Avatar removed.'];
                if(!empty($isAjax)){
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'ok', 'data' => $data]);
                    return;
                }
                $this->call->view('user/profile', $data);
                return;
            }

            // Otherwise treat as profile detail update (name/email/address/gender/dob/phone)
            $full_name = trim($this->io->post('full_name'));
            $email = trim($this->io->post('email'));
            $address = trim($this->io->post('address'));
            $gender = trim($this->io->post('gender'));
            $date_of_birth = trim($this->io->post('date_of_birth'));
            $phone = trim($this->io->post('phone'));
            $bio = trim($this->io->post('bio'));

            if(empty($full_name) || empty($email)){
                $data = ['user' => $user, 'error' => 'Full name and email are required.'];
                if(!empty($isAjax)){
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'error', 'data' => $data]);
                    return;
                }
                $this->call->view('user/edit_profile', $data);
                return;
            }

            // update in DB
            $update = [
                'full_name' => $full_name,
                'email' => $email,
                'address' => $address,
                'gender' => $gender,
                'date_of_birth' => $date_of_birth ?: NULL,
                'phone' => $phone,
                'bio' => $bio ?: NULL
            ];
            try{
                // Defensive: filter update keys to columns that actually exist in `users` table
                $existingCols = [];
                try{
                    $stmt = $this->UsersModel->raw('SHOW COLUMNS FROM `users`');
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if(!empty($rows)){
                        foreach($rows as $r){ if(isset($r['Field'])) $existingCols[] = $r['Field']; }
                    }
                } catch(Exception $e){
                    // if we can't read schema (permissions or other DB issue), fall back to a safe whitelist
                    $existingCols = [];
                }
                if(empty($existingCols)){
                    // Safe fallback: columns we expect in users table on most installs.
                    // Exclude fields that are optional in some deployments (e.g. 'gender') to avoid SQL errors.
                    $existingCols = ['user_id','full_name','email','address','date_of_birth','phone','bio','avatar','cover_photo','id_document','id_selfie','verification_requested','verification_requested_at','is_verified'];
                }
                // Filter update payload to only columns that exist or are in our safe fallback
                $update = array_intersect_key($update, array_flip($existingCols));

                $ok = $this->UsersModel->update(intval($user['user_id']), $update);
            } catch(Exception $e){
                $ok = false;
                // Log DB update exceptions for easier diagnosis
                $logDir = dirname(__DIR__,2) . '/runtime/logs'; if(!is_dir($logDir)) @mkdir($logDir, 0755, true);
                $entry  = "[".date('c')."] Profile DB update failed for user_id=".intval($user['user_id'])."\n";
                $entry .= "update_payload=" . json_encode($update) . "\n";
                $entry .= "exception=" . $e->getMessage() . "\n";
                @file_put_contents($logDir . '/profile.log', $entry, FILE_APPEND | LOCK_EX);
            }

            if($ok !== false){
                // reload authoritative user from DB so session matches persisted values
                $fresh = $this->refreshUserSession($user['user_id']);
                if($fresh !== false){ $user = $fresh; }
                // Request verification if all pieces are present (address + id_document + id_selfie)
                try{
                    $hasAddress = !empty($user['address']);
                    $hasIdDoc = !empty($user['id_document']);
                    $hasSelfie = !empty($user['id_selfie']);
                    $alreadyRequested = !empty($user['verification_requested']);
                    $alreadyVerified = !empty($user['is_verified']);
                    if($hasAddress && $hasIdDoc && $hasSelfie && !$alreadyRequested && !$alreadyVerified){
                        $now = date('Y-m-d H:i:s');
                        try{
                            $this->UsersModel->update(intval($user['user_id']), ['verification_requested' => 1, 'verification_requested_at' => $now]);
                        } catch(Exception $e){ @file_put_contents(dirname(__DIR__,2) . '/runtime/logs/profile.log', "[".date('c')."] Verification request DB update failed: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX); }
                        $user['verification_requested'] = 1; $user['verification_requested_at'] = $now; $this->session->set_userdata('user', $user);
                        // Inform user that request was submitted
                        $data = ['user' => $user, 'success' => 'Verification requested: an admin will review your documents shortly.'];
                        if(!empty($isAjax)){
                            header('Content-Type: application/json');
                            echo json_encode(['status' => 'ok', 'data' => $data]);
                            return;
                        }
                        $this->call->view('user/profile', $data);
                        return;
                    }
                } catch(Exception $e){ /* ignore verification errors */ }
                $data = ['user' => $user, 'success' => 'Profile updated successfully.'];
                if(!empty($isAjax)){
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'ok', 'data' => $data]);
                    return;
                }
                $this->call->view('user/profile', $data);
                return;
            }

            $data = ['user' => $user, 'error' => 'Unable to update profile. Please try again later.'];
            if(!empty($isAjax)){
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'data' => $data]);
                return;
            }
            $this->call->view('user/edit_profile', $data);
            return;
        }

        // Show edit form
        $this->call->view('user/edit_profile', ['user' => $user]);
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

    // Download invoice/receipt for a booking (returns a downloadable HTML invoice)
    public function invoice($booking_id = null)
    {
        if(!$this->session->has_userdata('user')){ redirect(site_url('auth/login')); return; }
        if(!$booking_id){ echo 'Booking id required'; return; }

        $user = $this->session->userdata('user');
        // fetch booking with room and user info
        try{
            $stmt = $this->BookingsModel->raw('SELECT b.*, r.room_number, r.room_type, u.full_name, u.email FROM bookings b LEFT JOIN rooms r ON r.room_id = b.room_id LEFT JOIN users u ON u.user_id = b.user_id WHERE b.booking_id = ? LIMIT 1', [intval($booking_id)]);
            $booking = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        } catch(Exception $e){ $booking = false; }

        if(!$booking){ http_response_code(404); echo 'Booking not found'; return; }
        // ensure the booking belongs to the current user (security)
        if(intval($booking['user_id']) !== intval($user['user_id'])){ http_response_code(403); echo 'Forbidden'; return; }
        // Only allow invoice download for approved or completed bookings
        $st = strtolower(trim($booking['status'] ?? ''));
        if(!in_array($st, ['approved','completed'], true)){
            // If AJAX request, return JSON 403
            try{ if($this->io->is_ajax()){ header('Content-Type: application/json'); http_response_code(403); echo json_encode(['status'=>'forbidden','message'=>'Invoice available only for approved or completed bookings.']); return; } } catch(Exception $e) {}
            flash_set('error', 'Invoice is available only for approved or completed bookings.'); redirect('user/bookings'); return;
        }

        // Build a professional hotel-style HTML invoice
        $hotelName = 'Sunset Hotel & Suites';
        $hotelAddress = '123 Seaview Avenue, Sunset City';
        $hotelPhone = '+63 912 345 6789';
        $hotelEmail = 'reservations@sunsethotel.example';
        $logoPath = PUBLIC_DIR . '/uploads/logo.png';
        $logoUrl = file_exists($logoPath) ? site_url('uploads/logo.png') : null;
        $bgPath = PUBLIC_DIR . '/uploads/invoice-bg.jpg';
        $bgUrl = file_exists($bgPath) ? site_url('uploads/invoice-bg.jpg') : null;

        $guestName = htmlspecialchars($booking['full_name'] ?? '');
        $guestEmail = htmlspecialchars($booking['email'] ?? '');
        $room = htmlspecialchars($booking['room_number'] ?? '') . ' — ' . htmlspecialchars($booking['room_type'] ?? '');
        $checkIn = htmlspecialchars($booking['check_in'] ?? '');
        $checkOut = htmlspecialchars($booking['check_out'] ?? '');
        $status = htmlspecialchars($booking['status'] ?? '');
        $amount = number_format((float)($booking['total_amount'] ?? 0), 2);
        $bookingRef = intval($booking['booking_id']);

        $invoiceHtml = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        $invoiceHtml .= '<title>Invoice #' . $bookingRef . ' - ' . htmlspecialchars($hotelName) . '</title>';
        $invoiceHtml .= '<style>
            :root{--accent:#0b74de;--muted:#6b6b6b;--bg:#ffffff}
            body{font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;margin:0;padding:24px;background:#f4f6f8;color:#111}
            .container{max-width:900px;margin:0 auto;background:var(--bg);box-shadow:0 2px 12px rgba(16,24,40,0.06);border-radius:8px;overflow:hidden}
            .header{padding:28px 32px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #eef2f6}
            .brand{display:flex;align-items:center;gap:18px}
            .brand img{height:64px;width:auto;border-radius:6px}
            .hotel{font-size:18px;font-weight:700;color:#0b2f6b}
            .hotel-sub{font-size:13px;color:var(--muted);margin-top:2px}
            .meta{font-size:14px;color:var(--muted);text-align:right}
            .body{padding:28px 32px}
            .section{display:flex;gap:18px}
            .col{flex:1}
            .col.small{flex:0 0 320px}
            table{width:100%;border-collapse:collapse;margin-top:14px}
            th,td{padding:10px;border-bottom:1px solid #f1f5f9;text-align:left;font-size:14px}
            th{background:#fafbfc;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:0.02em}
            .total-row td{border-top:2px solid #e6eef8;font-weight:700}
            .muted{color:var(--muted);font-size:13px}
            .footer{padding:20px 32px;background:#fbfdff;border-top:1px solid #eef2f6;font-size:13px;color:var(--muted)}
            .badge{display:inline-block;padding:6px 10px;border-radius:16px;background:var(--accent);color:#fff;font-weight:600;font-size:13px}
            @media print{ body{background:#fff} .container{box-shadow:none} }
        </style></head><body>';

        // If a background image exists, apply a subtle overlay so text remains readable
        $bgStyle = '';
        if(!empty($bgUrl)){
            $bgEsc = htmlspecialchars($bgUrl, ENT_QUOTES, 'UTF-8');
            $bgStyle = 'background-image: linear-gradient(rgba(255,255,255,0.92), rgba(255,255,255,0.92)), url("' . $bgEsc . '"); background-size: cover; background-position: center;';
        }
        $invoiceHtml .= '<div class="container" style="' . $bgStyle . '">';
        $invoiceHtml .= '<div class="header">';
        $invoiceHtml .= '<div class="brand">';
        if($logoUrl){ $invoiceHtml .= '<img src="' . htmlspecialchars($logoUrl) . '" alt="' . htmlspecialchars($hotelName) . ' logo">'; }
        $invoiceHtml .= '<div><div class="hotel">' . htmlspecialchars($hotelName) . '</div><div class="hotel-sub">' . htmlspecialchars($hotelAddress) . ' • ' . htmlspecialchars($hotelPhone) . '</div></div>';
        $invoiceHtml .= '</div>'; // brand

        $invoiceHtml .= '<div class="meta">';
        $invoiceHtml .= '<div style="font-size:16px;font-weight:700">Invoice</div>';
        $invoiceHtml .= '<div class="muted">Invoice #: ' . $bookingRef . '</div>';
        $invoiceHtml .= '<div class="muted">Date: ' . date('F j, Y') . '</div>';
        $invoiceHtml .= '<div style="margin-top:8px"><span class="badge">' . strtoupper($status) . '</span></div>';
        $invoiceHtml .= '</div>'; // meta
        $invoiceHtml .= '</div>'; // header

        $invoiceHtml .= '<div class="body">';
        $invoiceHtml .= '<div class="section">';
        $invoiceHtml .= '<div class="col">';
        $invoiceHtml .= '<h4 style="margin:0 0 8px 0">Bill To</h4>';
        $invoiceHtml .= '<div style="font-weight:600">' . $guestName . '</div>';
        if(!empty($guestEmail)) $invoiceHtml .= '<div class="muted">' . $guestEmail . '</div>';
        $invoiceHtml .= '</div>';

        $invoiceHtml .= '<div class="col small">';
        $invoiceHtml .= '<h4 style="margin:0 0 8px 0">Booking Details</h4>';
        $invoiceHtml .= '<div><strong>Room:</strong> ' . $room . '</div>';
        $invoiceHtml .= '<div><strong>Check-in:</strong> ' . $checkIn . '</div>';
        $invoiceHtml .= '<div><strong>Check-out:</strong> ' . $checkOut . '</div>';
        $invoiceHtml .= '</div>';
        $invoiceHtml .= '</div>'; // section

        // Charges table
        $invoiceHtml .= '<table aria-label="Charges">';
        $invoiceHtml .= '<thead><tr><th>Description</th><th style="width:140px;text-align:right">Amount</th></tr></thead><tbody>';
        $invoiceHtml .= '<tr><td>Room charge (' . $room . ')</td><td style="text-align:right">₱' . $amount . '</td></tr>';
        // If taxes or fees columns exist, try to show breakdown (best-effort)
        $tax = 0.00;
        $service = 0.00;
        // If booking stores tax/service fields, use them
        if(isset($booking['tax_amount'])){ $tax = (float)$booking['tax_amount']; $invoiceHtml .= '<tr><td>Taxes</td><td style="text-align:right">₱' . number_format($tax,2) . '</td></tr>'; }
        if(isset($booking['service_fee'])){ $service = (float)$booking['service_fee']; $invoiceHtml .= '<tr><td>Service charge</td><td style="text-align:right">₱' . number_format($service,2) . '</td></tr>'; }
        $sub = (float)$booking['total_amount'] - $tax - $service;
        if($sub < 0) $sub = 0.00;
        // Show subtotal only if tax/service were shown
        if($tax > 0 || $service > 0){ $invoiceHtml .= '<tr><td class="muted">Subtotal</td><td style="text-align:right">₱' . number_format($sub,2) . '</td></tr>'; }
        $invoiceHtml .= '<tr class="total-row"><td style="text-align:right">Total</td><td style="text-align:right">₱' . number_format((float)$booking['total_amount'],2) . '</td></tr>';
        $invoiceHtml .= '</tbody></table>';

        // Payment method info if available
        if(!empty($booking['payment_method']) || !empty($booking['payment_status'])){
            $invoiceHtml .= '<div style="margin-top:18px">';
            $invoiceHtml .= '<strong>Payment</strong><div class="muted">' . htmlspecialchars($booking['payment_method'] ?? '—') . ' • ' . htmlspecialchars($booking['payment_status'] ?? '—') . '</div>';
            $invoiceHtml .= '</div>';
        }

        $invoiceHtml .= '<div style="margin-top:18px;color:#333">';
        $invoiceHtml .= '<strong>Notes</strong><div class="muted" style="margin-top:6px">Thank you for choosing ' . htmlspecialchars($hotelName) . '. For changes or cancellations, please contact us at ' . htmlspecialchars($hotelPhone) . ' or ' . htmlspecialchars($hotelEmail) . '.</div>';
        $invoiceHtml .= '</div>';

        $invoiceHtml .= '</div>'; // body

        $invoiceHtml .= '<div class="footer">';
        $invoiceHtml .= '<div style="display:flex;justify-content:space-between;align-items:center">';
        $invoiceHtml .= '<div class="muted">' . htmlspecialchars($hotelName) . ' • ' . htmlspecialchars($hotelAddress) . ' • ' . htmlspecialchars($hotelPhone) . '</div>';
        $invoiceHtml .= '<div class="muted">Generated on ' . date('F j, Y \@ H:i') . '</div>';
        $invoiceHtml .= '</div>';
        $invoiceHtml .= '</div>'; // footer

        $invoiceHtml .= '</div>'; // container
        $invoiceHtml .= '</body></html>';

        // Force download as an HTML file named invoice-<id>.html
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="invoice-' . intval($booking['booking_id']) . '.html"');
        echo $invoiceHtml;
        return;
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