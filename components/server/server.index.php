<?php
if (INCLUDED !== true) exit;

header('Location: ' . spp_route_url('server', 'connect', array(), false), true, 302);
exit;
