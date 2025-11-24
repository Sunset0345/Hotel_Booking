<?php
$router->get('/user', 'UserController::index');
// user subpages: profile, bookings, messages
// allow POST to /user/profile for backward compatibility (some clients still post here)
$router->match('/user/profile', 'UserController::profile', ['GET', 'POST']);
// edit user profile (view + submit)
$router->match('/user/profile/edit', 'UserController::edit_profile', ['GET', 'POST']);
$router->get('/user/bookings', 'UserController::bookings');
$router->get('/user/invoice/{id}', 'UserController::invoice');
$router->match('/user/messages', 'UserController::messages', ['GET', 'POST']);
// Backwards-compatible alias: support singular /user/message URL which some users may type manually
$router->get('/user/message', 'UserController::messages');
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
/**
 * ------------------------------------------------------------------
 * LavaLust - an opensource lightweight PHP MVC Framework
 * ------------------------------------------------------------------
 *
 * MIT License
 *
 * Copyright (c) 2020 Ronald M. Marasigan
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package LavaLust
 * @author Ronald M. Marasigan <ronald.marasigan@yahoo.com>
 * @since Version 1
 * @link https://github.com/ronmarasigan/LavaLust
 * @license https://opensource.org/licenses/MIT MIT License
 */


// -------------------------------------------------------------------
// URI ROUTING
// -------------------------------------------------------------------
// Here is where you can register web routes for your application.


$router->get('/', 'Welcome::index');
$router->get('/rooms', 'RoomsController::index');
$router->get('/rooms/view/{id}', 'RoomsController::view');
$router->match('/bookings/create/{room_id}', 'BookingsController::create', ['GET', 'POST']);
// Debug helper (localhost only)
$router->get('/bookings/debug_insert', 'BookingsController::debug_insert');
$router->match('/users/create', 'UsersController::create', ['GET', 'POST']);
$router->match('/users/update/{id}', 'UsersController::update', ['GET', 'POST']);
$router->get('/users/delete/{id}', 'UsersController::delete');

$router->get('/home', 'Welcome::index');
$router->match('/auth/login', 'AuthController::login', ['GET', 'POST']);
$router->get('/auth/oauth/google', 'AuthController::oauth_google');
$router->get('/auth/oauth/facebook', 'AuthController::oauth_facebook');
$router->match('/auth/register', 'AuthController::register', ['GET', 'POST']);
$router->get('/auth/logout', 'AuthController::logout');
// Debug endpoints (local development only)
$router->get('/debug/session', 'DebugController::session');
$router->match('/debug/echo', 'DebugController::echo', ['GET','POST']);
// admin panel
$router->get('/admin', 'AdminController::index');
$router->get('/admin/messages_ajax', 'AdminController::messages_ajax');
// JSON-only admin API for conversation fetch
$router->get('/admin/messages_api', 'AdminController::messages_api');
$router->match('/admin/login', 'AdminController::login', ['GET', 'POST']);
$router->get('/admin/logout', 'AdminController::logout');
// admin subpages
$router->get('/admin/users', 'AdminController::users');
// admin user management
$router->get('/admin/users/edit/{id}', 'AdminController::users_edit');
$router->match('/admin/users/update/{id}', 'AdminController::users_update', ['POST']);
$router->get('/admin/users/delete/{id}', 'AdminController::users_delete');
// block/unblock user
$router->get('/admin/users/block/{id}', 'AdminController::users_block');
// verification requests (admin)
$router->get('/admin/verification_requests', 'AdminController::verification_requests');
$router->match('/admin/verification_approve/{id}', 'AdminController::verification_approve', ['GET','POST']);
$router->match('/admin/verification_reject/{id}', 'AdminController::verification_reject', ['GET','POST']);
$router->get('/admin/bookings', 'AdminController::bookings');
// admin audit logs
$router->get('/admin/audit', 'AdminController::audit');
$router->match('/admin/bookings/update/{id}', 'AdminController::bookings_update', ['GET', 'POST']);
// alias routes (singular) kept for backward-compatibility or alternate URLs
$router->get('/admin/booking', 'AdminController::bookings');
$router->match('/admin/booking/update/{id}', 'AdminController::bookings_update', ['GET', 'POST']);
$router->match('/admin/rooms/add', 'AdminController::rooms_add', ['GET', 'POST']);
$router->get('/admin/rooms', 'AdminController::rooms_list');
// Backwards-compatible alias used by older links/sites
$router->get('/admin/rooms_list', 'AdminController::rooms_list');
$router->get('/admin/rooms/available', 'AdminController::rooms_available');
$router->match('/admin/rooms/edit/{id}', 'AdminController::rooms_edit', ['GET', 'POST']);
$router->match('/admin/rooms/update/{id}', 'AdminController::rooms_update', ['POST']);
$router->get('/admin/rooms/delete/{id}', 'AdminController::rooms_delete');
// toggle availability
$router->get('/admin/rooms/toggle/{id}', 'AdminController::rooms_toggle');
$router->get('/admin/analytics', 'AdminController::analytics');
$router->match('/admin/messages', 'AdminController::messages', ['GET', 'POST']);
// diagnostics
$router->get('/diagnostics/db_health', 'DiagController::db_health');
