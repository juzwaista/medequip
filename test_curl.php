<?php
$ch = curl_init('http://localhost/medequip/public/admin/roles'); // Try standard xampp path
// We need to pass the inertia headers and session cookies, which is hard.
// Let's just create a route in web.php to dump the query.
