<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class AuthController extends Controller {
    public function __construct()
    {
        parent::__construct();
        $this->call->model('UsersModel');
        // Load AdminModel so admins can be authenticated from the regular login if needed
        $this->call->model('AdminModel');
    }

    public function login()
    {
        if($this->io->method() == 'post'){
            $email = $this->io->post('email');
            $password = $this->io->post('password');

            $user = $this->UsersModel->findByEmail($email);
            if($user && password_verify($password, $user['password'])){
                // use framework Session library
                $this->session->set_userdata('user', [
                    'user_id' => $user['user_id'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email']
                ]);
                redirect(site_url('user'));
                return;
            }

            // If no regular user found, allow admin authentication as a fallback
            $admin = $this->AdminModel->findByUsername($email);
            if($admin && isset($admin['password']) && password_verify($password, $admin['password'])){
                // set admin session and redirect to admin dashboard
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

    public function register()
    {
        if($this->io->method() == 'post'){
            $full_name = $this->io->post('full_name');
            $email = $this->io->post('email');
            $password = $this->io->post('password');

            if($this->UsersModel->findByEmail($email)){
                $data['error'] = 'Email already registered';
                $this->call->view('auth/register', $data);
                return;
            }

            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insert = [
                'full_name' => $full_name,
                'email' => $email,
                'password' => $hashed
            ];

            if($this->UsersModel->insert($insert)){
                redirect(site_url('auth/login'));
            }else{
                $data['error'] = 'Unable to create account';
                $this->call->view('auth/register', $data);
            }
        }else{
            $this->call->view('auth/register');
        }
    }

    public function logout()
    {
        // unset user session using framework Session library
        $this->session->unset_userdata('user');
        redirect(site_url(''));
    }
}
