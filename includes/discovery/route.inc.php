<?php

/* Copyright (C) 2014 Nicolas Armando <nicearma@yahoo.com> and Mathieu Millet <htam-net@github.net>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>. */


//This file is a litle diferent, because the route depend of the vrf, not of the context, 
//like the others, so if i use the context, you will have the same information n time the context how have the same VRF
global $debug;


$ids = array();

// For the moment only will be cisco and the version 3
if ($device['os_group'] == "cisco") {
    //RFC1213-MIB
    //$mib = "RFC1213-MIB";
    //IP-FORWARD-MIB
    $mib = "IP-FORWARD-MIB";

    // initialize context list array, add global context
    $device_contexts = array(array('context_name' => '', 'vrf_name' => 'global'));
//    $device_contexts = array();

    // pull contexts from database and add to context array
    foreach (dbFetchRows('SELECT `context_name`, `vrf_name` FROM `vrf_lite_cisco` WHERE `device_id` = ? ORDER BY `context_name` ASC', array($device['device_id'])) as $contexts) {
        array_push($device_contexts, $contexts);
    }

    $tableRoute = array();

    foreach($device_contexts as $context) {
        $device['context_name'] = $context['context_name'];

        ////////////////ipRouteDest//////////////////
        $oid = '.1.3.6.1.2.1.4.24.4.1.1';
        $resultHelp = snmp_walk($device, $oid, "-Osqnv", $mib, null);
//        $resultHelp = trim($resultHelp);
//        $resultHelp = str_replace("$oid.", "", $resultHelp);

        foreach (explode("\n", $resultHelp) as $ipRouteDest) {
            list($ip, $value) = explode(" ", $ipRouteDest);
            $tableRoute[$ip]['ipRouteDest'] = $value;
        }

        /////////////////ipRouteIfIndex//////////////
        $oid = '.1.3.6.1.2.1.4.24.4.1.5';
        $resultHelp = snmp_walk($device, $oid, "-Osqnv", $mib, null);
//        $resultHelp = trim($resultHelp);
//        $resultHelp = str_replace("$oid.", "", $resultHelp);

        foreach (explode("\n", $resultHelp) as $ipRouteIfIndex) {
            list($ip, $value) = explode(" ", $ipRouteIfIndex);
            $tableRoute[$ip]['ipRouteIfIndex'] = $value;
        }

        ///////////////ipRouteMetric1///////////////
        $oid = '.1.3.6.1.2.1.4.24.4.1.11';
        $resultHelp = snmp_walk($device, $oid, "-Osqnv", $mib, null);
//        $resultHelp = trim($resultHelp);
//        $resultHelp = str_replace("$oid.", "", $resultHelp);

        foreach (explode("\n", $resultHelp) as $ipRouteMetric) {
            list($ip, $value) = explode(" ", $ipRouteMetric);
            $tableRoute[$ip]['ipRouteMetric'] = $value;
        }

        ////////////ipRouteNextHop//////////////////
        $oid = '.1.3.6.1.2.1.4.24.4.1.4';
        $resultHelp = snmp_walk($device, $oid, "-Osqnv", $mib, null);
//        $resultHelp = trim($resultHelp);
//        $resultHelp = str_replace("$oid.", "", $resultHelp);
 
       foreach (explode("\n", $resultHelp) as $ipRouteNextHop) {
            list($ip, $value) = explode(" ", $ipRouteNextHop);
            $tableRoute[$ip]['ipRouteNextHop'] = $value;
        }

        ////////////ipRouteType/////////////////////
        $oid = '.1.3.6.1.2.1.4.24.4.1.6';
        $resultHelp = snmp_walk($device, $oid, "-Osqnv", $mib, null);
//        $resultHelp = trim($resultHelp);
//        $resultHelp = str_replace("$oid.", "", $resultHelp);

        foreach (explode("\n", $resultHelp) as $ipRouteType) {
            list($ip, $value) = explode(" ", $ipRouteType);
            $tableRoute[$ip]['ipRouteType'] = $value;
        }

        ///////////ipRouteProto//////////////////////
        $oid = '.1.3.6.1.2.1.4.24.4.1.7';
        $resultHelp = snmp_walk($device, $oid, "-Osqnv", $mib, null);
//        $resultHelp = trim($resultHelp);
//        $resultHelp = str_replace("$oid.", "", $resultHelp);
//        $resultHelp = str_replace("netmgmt", "static", $resultHelp);

        foreach (explode("\n", $resultHelp) as $ipRouteProto) {
            list($ip, $value) = explode(" ", $ipRouteProto);
            $tableRoute[$ip]['ipRouteProto'] = $value;
        }

        /*
          ///////////ipRouteAge//////////////////////
          $oid = '.1.3.6.1.2.1.4.24.4.1.8';
          $resultHelp = snmp_walk($device, $oid, "-Osqn", $mib, NULL);
          $resultHelp = str_replace("$oid.", "", $resultHelp);

          foreach (explode("\n", $resultHelp) as $ipRouteAge) {
              list($ip,$value)=explode(" ",$ipRouteAge);
              $tableRoute[$ip]['ipRouteAge']=$value;
          } */

        ///////////ipRouteMask//////////////////////
        $oid = '.1.3.6.1.2.1.4.24.4.1.2';
        $resultHelp = snmp_walk($device, $oid, "-Osqnv", $mib, null);
//        $resultHelp = trim($resultHelp);
//        $resultHelp = str_replace("$oid.", "", $resultHelp);

        foreach (explode("\n", $resultHelp) as $ipRouteMask) {
            list($ip, $value) = explode(" ", $ipRouteMask);
            $tableRoute[$ip]['ipRouteMask'] = $value;
        }

        if ($debug) {
            echo 'Table routage';
            var_dump($tableRoute);
        }
 
        $vrf = $context['vrf_name'];

        foreach ($tableRoute as $ipRoute) {
            if (empty($ipRoute['ipRouteDest']) || $ipRoute['ipRouteDest'] == '') {
                continue;
            }

            $oldRouteRow = dbFetchRow('select * from route where device_id = ? AND ipRouteDest = ? AND context_name = ?', array($device['device_id'], $ipRoute['ipRouteDest'], $device['context_name']));
            if (!empty($oldRouteRow)) {
                unset($oldRouteRow['discoveredAt']);
                $changeRoute = array();
                foreach ($ipRoute as $key => $value) {
                    if ($oldRouteRow[$key] != $value) {
                        $changeRoute[$key] = $value;
                    }
                }
                if (!empty($changeRoute)) {
                    dbUpdate($changeRoute, 'route', 'device_id = ? and ipRouteDest = ? and context_name = ? and vrf = ?', array($device['device_id'], $ipRoute['ipRouteDest'], $device['context_name'], $vrf));
                }
            } else {
                $toInsert = array_merge($ipRoute, array('device_id' => $device['device_id'], 'context_name' => $device['context_name'], 'vrf' => $vrf, 'discoveredAt' => time()));
                dbInsert($toInsert, 'route');
            }
        }
    } //end context for loop

    //cleanup routes for missing contexts
    foreach (dbFetchRow('SELECT DISTINCT `context_name` FROM `route` WHERE `device_id` = ?', array($device['device_id'])) as $db_context) {
        if ($db_context['context_name'] != '' && !array_search($db_context['context_name'], $device_contexts)) {
            dbDelete('route', '`context_name` = ? AND `device_id` = ?', array($db_context['context_name'], $device['device_id']));
        }
    }

} //end if statement
