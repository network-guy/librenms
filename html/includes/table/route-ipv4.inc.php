<?php

$param = array();

$sql = " FROM `route` as R, `devices` as D " ;

if (is_admin() === false && is_read() === false) {
    $sql    .= ' LEFT JOIN `devices_perms` AS `DP` ON `D`.`device_id` = `DP`.`device_id`';
    $where  .= ' AND `DP`.`user_id`=?';
    $param[] = $_SESSION['user_id'];
}

$sql .= " WHERE `R`.`device_id` = `D`.`device_id` $where ";

if (is_numeric($_POST['device_id'])) {
    $sql    .= ' AND R.device_id = ?';
    $param[] = $_POST['device_id'];
}

if (isset($_POST['vrf'])) {
    $sql  .= ' AND `R`.`vrf` = ?';
    $param[] = $_POST['vrf'];
}

if (isset($_POST['searchPhrase']) && !empty($_POST['searchPhrase'])) {
    $ip_search = '%'.mres(trim($_POST['searchPhrase'])).'%';

    $sql .= ' AND (`vrf` LIKE ? OR`ipRouteDest` LIKE ? OR `ipRouteNextHop` LIKE ? OR `ipRouteProto` LIKE ?)';
    $param[] = trim($_POST['searchPhrase']);
    $param[] = $ip_search;
    $param[] = $ip_search;
    $param[] = trim($_POST['searchPhrase']);
}

$count_sql = "SELECT COUNT(`R`.`ipRouteDest`) $sql";

$total = dbFetchCell($count_sql, $param);
if (empty($total)) {
    $total = 0;
}

if (!isset($sort) || empty($sort)) {
    $sort = '`vrf` ASC, `ipRouteDest` ASC';
}

$sql .= " ORDER BY $sort";

if (isset($current)) {
    $limit_low  = (($current * $rowCount) - ($rowCount));
    $limit_high = $rowCount;
}

if ($rowCount != -1) {
    $sql .= " LIMIT $limit_low,$limit_high";
}

$sql = "SELECT * $sql";

foreach (dbFetchRows($sql, $param) as $entry) {
    $entry = cleanPort($entry);

    $interface = dbFetchRow('SELECT * FROM `ports` WHERE `ifIndex` = ? AND `device_id` = ?', array($entry['ipRouteIfIndex'], $entry['device_id']));

    $response[] = array(
        'ipRouteDest'    => $entry['ipRouteDest'],
        'ipRouteMask'    => $entry['ipRouteMask'],
        'interface'      => generate_port_link($interface, makeshortif(fixifname(cleanPort($interface['ifDescr'])))).' '.$error_img,
        'ipRouteMetric'  => $entry['ipRouteMetric'],
        'ipRouteNextHop' => $entry['ipRouteNextHop'],
        'ipRouteProto'   => $entry['ipRouteProto'],
    );
}

$output = array(
    'current'  => $current,
    'rowCount' => $rowCount,
    'rows'     => $response,
    'total'    => $total,
);
echo _json_encode($output);
