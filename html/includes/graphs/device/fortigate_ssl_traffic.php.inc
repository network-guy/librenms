<?php

$rrd_filename = rrd_name($device['hostname'], 'fortigate_ssl_traffic');

$ds_in  = 'InOctets';
$ds_out = 'OutOctets';

require 'includes/graphs/generic_data.inc.php';
