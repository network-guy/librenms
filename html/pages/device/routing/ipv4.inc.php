<?php

$link_array = array(
               'page'   => 'device',
               'device' => $device['device_id'],
               'tab'    => 'routing',
               'proto'  => 'ipv4',
              );

$vrfs = dbFetchRows("SELECT DISTINCT `vrf` FROM `route` WHERE `device_id` = ? ORDER BY `vrf` ASC", array($device['device_id']));

if (!isset($vars['vrf'])) {
    $vars['vrf'] = $vrfs[0]['vrf'];
}

print_optionbar_start();

echo "<span style='font-weight: bold;'>VRF</span> &#187; ";

$menu_options = array();
foreach($vrfs as $vrf) {
    $menu_options[$vrf['vrf']] = $vrf['vrf'];
}

foreach ($menu_options as $option => $text) {
    if ($vars['vrf'] == $option) {
        echo "<span class='pagemenu-selected'>";
    }

    echo generate_link($text, $link_array, array('vrf' => $option));
    if ($vars['vrf'] == $option) {
        echo '</span>';
    }

    echo ' | ';
}

print_optionbar_end();

$no_refresh = true;
?>
<table id="route_ipv4" class="table table-condensed table-hover table-striped">
    <thead>
        <tr>
            <th data-column-id="ipRouteDest">Destination</th>
            <th data-column-id="ipRouteMask">Mask</th>
            <th data-column-id="ipRouteNextHop">Next Hop</th>
            <th data-column-id="ipRouteProto">Protocol</th>
            <th data-column-id="ipRouteMetric">Metric</th>
            <th data-column-id="interface">Interface</th>
        </tr>
    </thead>
</table>

<script>

var grid = $("#route_ipv4").bootgrid({
    ajax: true,
    post: function ()
    {
       return {
            id: "route-ipv4",
            device_id: "<?php echo $device['device_id']; ?>",
            vrf: "<?php echo $vars['vrf']; ?>",
        };
    },
    url: "ajax_table.php"
});
</script>
