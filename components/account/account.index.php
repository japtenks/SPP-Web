<?php
if(INCLUDED!==true)exit;

if (($user['id'] ?? -1) > 0) {
    header('Location: ' . spp_route_url('account', 'manage', array(), false), true, 302);
} else {
    header('Location: ' . spp_route_url('account', 'login', array(), false), true, 302);
}
exit;
?>
