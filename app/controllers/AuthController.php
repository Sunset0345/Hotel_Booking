<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class AuthController extends Controller {
    public function __construct() {
        parent::__construct();
        $this->call->model('UsersModel');
        $this->call->model('AdminModel');
    }

    public function login() {
        if($this->io->method() == 'post'){
            $email = $this->io->post('email');
            $password = $this->io->post('password');
            $user = $this->UsersModel->findByEmail($email);
            if($user && password_verify($password, $user['password'])){
                $this->session->set_userdata('user', [
                    'user_id' => $user['user_id'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'is_verified' => isset($user['is_verified']) ? (int)$user['is_verified'] : 0,
                    'verification_requested' => isset($user['verification_requested']) ? (int)$user['verification_requested'] : 0
                ]);
                redirect(site_url('user'));
                return;
            }
            $admin = $this->AdminModel->findByUsername($email);
            if($admin && isset($admin['password']) && password_verify($password, $admin['password'])){
                $this->session->set_userdata('admin', [
                    'admin_id' => $admin['admin_id'],
                    'username' => $admin['username'],
                    'full_name' => $admin['full_name'],
                    'role' => $admin['role']
                ]);
                redirect(site_url('admin'));
                return;
            }
            $data['error'] = 'Invalid credentials';
            $this->call->view('auth/login', $data);
        }else{
            $this->call->view('auth/login');
        }
    }

    public function register() {
        // Step 1: Registration form submitted or resend OTP
        if ($this->io->method() == 'post' && ($this->io->post('full_name') && $this->io->post('email') && $this->io->post('password') || $this->io->post('resend_otp'))) {
            if ($this->io->post('resend_otp')) {
                // Resend OTP logic
                $full_name = $this->session->userdata('reg_full_name');
                $email = $this->session->userdata('reg_email');
                $password = $this->session->userdata('reg_password');
            } else {
                $full_name = $this->io->post('full_name');
                $email = $this->io->post('email');
                $password = $this->io->post('password');
                if ($this->UsersModel->findByEmail($email)) {
                    $data['error'] = 'Email already registered';
                    $this->call->view('auth/register', $data);
                    return;
                }
                $this->session->set_userdata('reg_full_name', $full_name);
                $this->session->set_userdata('reg_email', $email);
                $this->session->set_userdata('reg_password', $password);
            }
            // Generate OTP and store creation time
            $otp = rand(100000, 999999);
            $this->session->set_userdata('otp', $otp);
            $this->session->set_userdata('otp_created', time());
            // Send OTP to email
            $subject = 'Your OTP for Blue Lagoon Hotel Registration';
            $message = 'Your OTP code is: ' . $otp . "\nThis code will expire in 5 minutes.";
            // Ensure sender is set (use SMTP from config or fallback)
            $from_email = config_item('smtp_from') ? config_item('smtp_from') : 'no-reply@localhost';
            $from_name = config_item('smtp_from_name') ? config_item('smtp_from_name') : 'Blue Lagoon Hotel';
            try {
                $this->email->sender($from_email, $from_name);
            } catch (Exception $e) {
                // ignore invalid sender here, recipient/subject/body still set
            }
            $this->email->recipient($email);
            $this->email->subject($subject);
            $this->email->email_content($message);
            $sent = $this->email->send();
            if (! $sent) {
                // log to runtime logs for debugging
                if (function_exists('log_message')) {
                    log_message('error', 'OTP email send failed to: ' . $email);
                } else {
                    @file_put_contents(dirname(__DIR__,2) . '/runtime/logs/email.log', date('c') . " - OTP send failed to: $email\n", FILE_APPEND);
                }
            }
            $data['email'] = $email;
            $data['resent'] = $this->io->post('resend_otp') ? true : false;
            $this->call->view('auth/verify_otp', $data);
            return;
        }
        // Step 2: OTP verification
        if ($this->io->method() == 'post' && $this->io->post('otp')) {
            $input_otp = trim($this->io->post('otp'));
            // Log OTP verification attempt for debugging
            $logPath = dirname(__DIR__,2) . '/runtime/logs/otp.log';
            $logMsg = date('c') . ' - OTP verify attempt for session ' . session_id() . ": input='$input_otp'\n";
            @file_put_contents($logPath, $logMsg, FILE_APPEND);
            $session_otp = $this->session->userdata('otp');
            $otp_created = $this->session->userdata('otp_created');
            $full_name = $this->session->userdata('reg_full_name');
            $email = $this->session->userdata('reg_email');
            $password = $this->session->userdata('reg_password');
            $now = time();
            @file_put_contents($logPath, date('c') . ' - session otp=' . var_export($session_otp, true) . ", reg_email='" . $email . "' otp_created=" . var_export($otp_created, true) . "\n", FILE_APPEND);
            if (!$session_otp || !$full_name || !$email || !$password || !$otp_created) {
                $this->session->unset_userdata('otp');
                $this->session->unset_userdata('otp_created');
                $this->session->unset_userdata('reg_full_name');
                $this->session->unset_userdata('reg_email');
                $this->session->unset_userdata('reg_password');
                $data['error'] = 'Session expired. Please register again.';
                $this->call->view('auth/register', $data);
                return;
            }
            if ($now - $otp_created > 300) { // 5 minutes
                $this->session->unset_userdata('otp');
                $this->session->unset_userdata('otp_created');
                $this->session->unset_userdata('reg_full_name');
                $this->session->unset_userdata('reg_email');
                $this->session->unset_userdata('reg_password');
                $data['error'] = 'OTP expired. Please register again.';
                $this->call->view('auth/register', $data);
                return;
            }
            if ((string)$input_otp !== (string)$session_otp) {
                @file_put_contents($logPath, date('c') . " - OTP mismatch: input='$input_otp' session='$session_otp'\n", FILE_APPEND);
                $data['error'] = 'Invalid OTP. Please try again.';
                $data['email'] = $email;
                $this->call->view('auth/verify_otp', $data);
                return;
            }
            // Create account
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insert = [
                'full_name' => $full_name,
                'email' => $email,
                'password' => $hashed
            ];
            if ($this->UsersModel->insert($insert)) {
                // Clear session OTP data
                $this->session->unset_userdata('otp');
                $this->session->unset_userdata('otp_created');
                $this->session->unset_userdata('reg_full_name');
                $this->session->unset_userdata('reg_email');
                $this->session->unset_userdata('reg_password');
                // Show confirmation page
                $data = [];
                $data['email'] = $email;
                $this->call->view('auth/registration_success', $data);
            } else {
                $data['error'] = 'Unable to create account';
                $this->call->view('auth/register', $data);
            }
            return;
        }
        // Default: show registration form
        $this->call->view('auth/register');
    }

    public function logout() {
        $this->session->unset_userdata('user');
        redirect(site_url(''));
    }

    // Google OAuth login handler
    public function oauth_google() {
        require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
        $client_id = function_exists('config_item') ? config_item('google_client_id') : null;
        $client_secret = function_exists('config_item') ? config_item('google_client_secret') : null;
        $redirect_uri = function_exists('config_item') ? config_item('google_redirect_uri') : 'http://localhost:3000/auth/oauth/google';
        $client = new \Google_Client();
        $client->setClientId($client_id);
        $client->setClientSecret($client_secret);
        $client->setRedirectUri($redirect_uri);
        $client->addScope('email');
        $client->addScope('profile');
        if (!isset($_GET['code'])) {
            $auth_url = $client->createAuthUrl();
            header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
            exit();
        } else {
            $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
            if (isset($token['error'])) {
                echo 'Google OAuth error: ' . htmlspecialchars($token['error_description']);
                return;
            }
            $client->setAccessToken($token['access_token']);
            $oauth2 = new \Google_Service_Oauth2($client);
            $userinfo = $oauth2->userinfo->get();
            $email = $userinfo->email;
            $full_name = $userinfo->name;
            $user = $this->UsersModel->findByEmail($email);
            if (!$user) {
                $insert = [
                    'full_name' => $full_name,
                    'email' => $email,
                    'password' => password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT)
                ];
                $this->UsersModel->insert($insert);
                $user = $this->UsersModel->findByEmail($email);
            }
            $this->session->set_userdata('user', [
                'user_id' => $user['user_id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'is_verified' => isset($user['is_verified']) ? (int)$user['is_verified'] : 0,
                'verification_requested' => isset($user['verification_requested']) ? (int)$user['verification_requested'] : 0
            ]);
            redirect(site_url('user'));
        }
    }

    // Facebook OAuth login handler
    public function oauth_facebook() {
        require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
        $fb_app_id = function_exists('config_item') ? config_item('facebook_app_id') : null;
        $fb_app_secret = function_exists('config_item') ? config_item('facebook_app_secret') : null;
        $fb_redirect_uri = function_exists('config_item') ? config_item('facebook_redirect_uri') : 'http://localhost:3000/auth/oauth/facebook';
        $fb = new \Facebook\Facebook([
            'app_id' => $fb_app_id,
            'app_secret' => $fb_app_secret,
            'default_graph_version' => 'v12.0',
        ]);
        $helper = $fb->getRedirectLoginHelper();
        $permissions = ['email'];
        if (!isset($_GET['code'])) {
            $loginUrl = $helper->getLoginUrl($fb_redirect_uri, $permissions);
            header('Location: ' . $loginUrl);
            exit();
        } else {
            try {
                $accessToken = $helper->getAccessToken($fb_redirect_uri);
            } catch (\Facebook\Exceptions\FacebookResponseException $e) {
                echo 'Facebook Graph returned an error: ' . $e->getMessage();
                exit;
            } catch (\Facebook\Exceptions\FacebookSDKException $e) {
                echo 'Facebook SDK returned an error: ' . $e->getMessage();
                exit;
            }
            if (!isset($accessToken)) {
                echo 'Facebook OAuth error: No access token.';
                return;
            }
            try {
                $response = $fb->get('/me?fields=id,name,email', $accessToken);
                $userNode = $response->getGraphUser();
            } catch (\Facebook\Exceptions\FacebookResponseException $e) {
                echo 'Facebook Graph returned an error: ' . $e->getMessage();
                exit;
            } catch (\Facebook\Exceptions\FacebookSDKException $e) {
                echo 'Facebook SDK returned an error: ' . $e->getMessage();
                exit;
            }
            $email = $userNode->getField('email');
            $full_name = $userNode->getField('name');
            $user = $this->UsersModel->findByEmail($email);
            if (!$user) {
                $insert = [
                    'full_name' => $full_name,
                    'email' => $email,
                    'password' => password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT)
                ];
                $this->UsersModel->insert($insert);
                $user = $this->UsersModel->findByEmail($email);
            }
            $this->session->set_userdata('user', [
                'user_id' => $user['user_id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'is_verified' => isset($user['is_verified']) ? (int)$user['is_verified'] : 0,
                'verification_requested' => isset($user['verification_requested']) ? (int)$user['verification_requested'] : 0
            ]);
            redirect(site_url('user'));
        }
    }
}
