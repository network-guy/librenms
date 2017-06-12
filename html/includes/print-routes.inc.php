<?php

// fixme new url format
if (is_integer($i / 2)) {
    $bg_colour = $list_colour_a;
} else {
    $bg_colour = $list_colour_b;
}

echo "<tr bgcolor='$bg_colour'>";

echo '<td width=75 class=list-bold>'.$routes['ipRouteDest'].'</td>';
echo '<td width=75 class=box-desc>'.$routes['ipRouteMask'].'</td>';
echo '<td width=75 class=box-desc>'.$routes['ipRouteNextHop'].'</td>';
echo '<td width=75 class=box-desc>'.$routes['ipRouteProto'].'</td>';

$interface = dbFetchRows('SELECT * FROM `ports` WHERE `device_id` = ? and `ifIndex` = ?', array($device['device_id'], $routes['ipRouteIfIndex']));
$port = $interface[0];

echo '<td width=75 class=list-bold>'.$routes['port_sep'].generate_port_link($port, makeshortif($port['ifDescr'])).'</td>';

echo '</tr>';
