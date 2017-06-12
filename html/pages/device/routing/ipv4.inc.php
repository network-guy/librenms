<?php

$link_array = array(
               'page'   => 'device',
               'device' => $device['device_id'],
               'tab'    => 'routing',
               'proto'  => 'ipv4',
              );

//echo(generate_link("Basic", $link_array,array('view'=>'basic')));
if (!isset($vars['view'])) {
    $vars['view'] = 'basic';
}

print_optionbar_start();

echo "<span style='font-weight: bold;'>Routing Table</span> &#187; ";

$menu_options = array('basic' => 'Basic',
// 'detail' => 'Detail',
                );

if (!$_GET['opta']) {
    $_GET['opta'] = 'basic';
}

$sep = '';
foreach ($menu_options as $option => $text) {
    if ($vars['view'] == $option) {
        echo "<span class='pagemenu-selected'>";
    }

    echo generate_link($text, $link_array, array('view' => $option));
    if ($vars['view'] == $option) {
        echo '</span>';
    }

    echo ' | ';
}

unset($sep);

print_optionbar_end();

echo "<div style='margin: 5px;'><table border=0 cellspacing=0 cellpadding=5 width=100%>";
echo '<tr style="height: 30px"><th>IPv4 Prefix</th><th>Netmask</th><th>Next Hop</th><th>Protocol</th><th>Interface</th></tr>';

if (!isset($vars['startrow']) and !is_numeric($vars['startrow'])) {
    $startrow = 0;
} else {
    $startrow = (int)$vars['startrow'];
}

$i = 0;

foreach (dbFetchRows("SELECT * FROM `route` WHERE `device_id` = ? ORDER BY `ipRouteDest` LIMIT $startrow, 25", array($device['device_id'])) as $routes) {
    include 'includes/print-routes.inc.php';

    $i++;
}

echo '</table></div>';

$prev = $startrow - 25;
if ($prev >= 0) {
    echo (generate_link("Previous", $link_array, array('startrow'=>($prev))));
}

echo "  ";

echo (generate_link("Next", $link_array, array('startrow'=>($startrow+25))));
