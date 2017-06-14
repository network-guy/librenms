<?php

use LibreNMS\RRD\RrdDefinition;

$fnSysVersion = snmp_get($device, 'FORTINET-FORTIGATE-MIB::fgSysVersion.0', '-Ovq');
$serial       = snmp_get($device, 'ENTITY-MIB::entPhysicalSerialNum.1', '-Ovq');
$version                 = preg_replace('/(.+),(.+),(.+)/', '\\1||\\2||\\3', $fnSysVersion);
list($version,$features) = explode('||', $version);
if (isset($rewrite_fortinet_hardware[$poll_device['sysObjectID']])) {
    $hardware = $rewrite_fortinet_hardware[$poll_device['sysObjectID']];
}
if (empty($hardware)) {
    $hardware = snmp_get($device, 'ENTITY-MIB::entPhysicalModelName.1', '-Ovq');
}

$sessions = snmp_get($device, 'FORTINET-FORTIGATE-MIB::fgSysSesCount.0', '-Ovq');
if (is_numeric($sessions)) {
    $rrd_def = RrdDefinition::make()->addDataset('sessions', 'GAUGE', 0, 3000000);

    print "Sessions: $sessions\n";
    $fields = array(
        'sessions' => $sessions,
    );

    $tags = compact('rrd_def');
    data_update($device, 'fortigate_sessions', $tags, $fields);
    $graphs['fortigate_sessions'] = true;
}

$cpu_usage = snmp_get($device, 'FORTINET-FORTIGATE-MIB::fgSysCpuUsage.0', '-Ovq');
if (is_numeric($cpu_usage)) {
    $rrd_def = RrdDefinition::make()->addDataset('LOAD', 'GAUGE', -1, 100);

    echo "CPU: $cpu_usage%\n";
    $fields = array(
        'LOAD' => $cpu_usage,
    );

    $tags = compact('rrd_def');
    data_update($device, 'fortigate_cpu', $tags, $fields);
    $graphs['fortigate_cpu'] = true;
}

$ssl_traffic_in = snmp_get($device, 'fgVpnSslTunnelBytesIn', '-OQv', 'FORTINET-FORTIGATE-MIB');
if ($ssl_traffic_in) {
    foreach ($ssl_traffic_in as $octets) {
        $ssl_traffic_in_octets += $octets;
    }
}

$ssl_traffic_out = snmp_get($device, 'fgVpnSslTunnelBytesOut', '-OQv', 'FORTINET-FORTIGATE-MIB');
if ($ssl_traffic_out) {
    foreach ($ssl_traffic_out as $octets) {
        $ssl_traffic_out_octets += $octets;
    }
}

if (is_numeric($ssl_traffic_in_octets) & is_numeric($ssl_traffic_out_octets)) {
    $rrd_def = RrdDefinition::make()
        ->addDataset('InOctets', 'GAUGE', 0)
        ->addDataset('OutOctets', 'GAUGE', 0);

    $fields = array(
        'InOctets' => $in_octets,
        'OutOctets' => $out_octets,
    );

    $tags = compact('rrd_def');
    data_update($device, 'fortigate_ssl_traffic', $tags, $fields);

    $graphs['fortigate_ssl_traffic'] = true;
    echo 'SSLVPN Tunnel Bandwidth';
}

$ssl_tunnels = snmp_get($device, 'fgVpnSslStatsActiveTunnels', '-OQv', 'FORTINET-FORTIGATE-MIB');

$rrd_def = RrdDefinition::make()
    ->addDataset('Tunnels', 'GAUGE', 0);

$fields = array(
    'Tunnels' => array_sum($ssl_tunnels),
);

$tags = compact('rrd_def');
data_update($device, 'fortigate_ssl_tunnels', $tags, $fields);

$graphs['fortigate_ssl_tunnels'] = true;
echo 'SSLVPN Active Tunnels';
