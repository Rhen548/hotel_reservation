<?php
/**
 * All routes here
 */

$router->get('/', 'app/views/homepage');

$router->get('/homepage', 'app/views/homepage.php');


$router->get('/search-rooms', 'app/views/booking/search.php');
$router->post('/search-rooms', 'app/views/booking/search.php');

$router->get('/book-room', 'app/views/booking/book.php');
$router->post('/book-room', 'app/views/booking/book.php');

$router->get('/booking-success', 'app/views/booking/success.php');




$router->get('/admin/reservations', 'app/views/admin/reservations.php');

$router->post('/admin/reservations/action', 'app/views/admin/reservation_action.php');


$router->get('/admin/payments', 'app/views/admin/payments.php');
$router->post('/admin/payments/store', 'app/views/admin/payment_store.php');


$router->get('/admin/reports', 'app/views/admin/reports.php');


$router->get('/admin/rooms', 'app/views/admin/rooms.php');
$router->get('/admin/rooms/create', 'app/views/admin/room_create.php');
$router->post('/admin/rooms/store', 'app/views/admin/room_store.php');

$router->get('/admin/rooms/edit', 'app/views/admin/room_edit.php');
$router->post('/admin/rooms/update', 'app/views/admin/room_update.php');

$router->post('/admin/rooms/delete', 'app/views/admin/room_delete.php');


$router->get('/admin/room-images', 'app/views/admin/room_images.php');
$router->post('/admin/room-images/store', 'app/views/admin/room_image_store.php');
$router->post('/admin/room-images/delete', 'app/views/admin/room_image_delete.php');


$router->get('/room', 'app/views/booking/room_details.php');

$router->get('/track-reservation', 'app/views/booking/track_reservation.php');
$router->post('/track-reservation', 'app/views/booking/track_reservation.php');


$router->get('/admin/login', 'app/views/admin/login.php');
$router->post('/admin/login', 'app/views/admin/login.php');
$router->get('/admin/logout', 'app/views/admin/logout.php');

$router->get('/admin', 'app/views/admin/index.php');


$router->get('/booking-confirmation', 'app/views/booking/booking_confirmation.php');

$router->get('/admin/audit-logs', 'app/views/admin/audit_logs.php');


$router->get('/customer-signin', 'app/views/customer_signin.php');
$router->post('/customer-signin', 'app/views/customer_signin.php');


$router->get('/customer-profile', 'app/views/customer_profile.php');
$router->post('/customer-profile', 'app/views/customer_profile.php');

$router->get('/customer-signout', 'app/views/customer_signout.php');

$router->get('/my-reservations', 'app/views/my_reservations.php');

$router->post('/reservation/cancel-request', 'app/views/booking/cancel_reservation_request.php');

$router->post('/admin/cancel-request-action', 'app/views/admin/cancel_request_action.php');