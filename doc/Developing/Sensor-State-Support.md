source: Developing/Sensor-State-Support.md
# Sensor State Support

### Introduction

In this section we are briefly going to walk through, what it takes to write sensor state support.
We will also briefly get around the concepts of the current sensor state monitoring.

### Logic

For sensor state monitoring, we have 4 DB tables we need to concentrate about.
- sensors
- state_indexes
- state_translations
- sensors_to_state_indexes

We will just briefly tie a comment to each one of them.

#### sensors

*Each time a sensor needs to be polled, the system needs to know which sensor is it that it need to poll, at what oid is this sensor located and what class the sensor is etc.
This information is fetched from the sensors table.*

#### state_indexes

*Is where we keep track of which state sensors we monitor.*

#### state_translations

*Is where we map the possible returned state sensor values to a generic LibreNMS value, in order to make displaying and alerting more generic.
We also map these values to the actual state sensor(state_index) where these values are actually returned from.*


*The LibreNMS generic states is derived from Nagios:*

```
0 = OK

1 = Warning

2 = Critical

3 = Unknown
```

#### sensors_to_state_indexes

*Is as you might have guessed, where the sensor_id is mapped to a state_index_id.*

### Example

For YAML based state discovery:

```yaml
mib: NETBOTZV2-MIB
modules:
    sensors:
        state:
            -
                oid: dryContactSensorTable
                value: dryContactSensorValue
                num_oid: .1.3.6.1.4.1.5528.100.4.2.1.1.2.
                descr: dryContactSensorLabel
                index: 'dryContactSensor.{{ $index }}'
                state_name: dryContactSensor
                states:
                    - { descr: 'null', graph: 0, value: -1, generic: 3 }
                    - { descr: open, graph: 0, value: 0, generic: 0 }
                    - { descr: closed, graph: 0, value: 1, generic: 2 }
            -
                oid: doorSwitchSensorTable
                value: doorSwitchSensorValue
                num_oid: .1.3.6.1.4.1.5528.100.4.2.2.1.2.
                descr: doorSwitchSensorLabel
                index: 'doorSwitchSensor.{{ $index }}'
                state_name: doorSwitchSensor
                states:
                    - { descr: 'null', graph: 0, value: -1, generic: 3 }
                    - { descr: open, graph: 0, value: 0, generic: 0 }
                    - { descr: closed, graph: 0, value: 1, generic: 2 }
            -
                oid: cameraMotionSensorTable
                value: cameraMotionSensorValue
                num_oid: .1.3.6.1.4.1.5528.100.4.2.3.1.2.
                descr: cameraMotionSensorLabel
                index: 'cameraMotionSensor.{{ $index }}'
                state_name: cameraMotionSensor
                states:
                    - { descr: 'null', graph: 0, value: -1, generic: 3 }
                    - { descr: noMotion, graph: 0, value: 0, generic: 0 }
                    - { descr: motionDetected, graph: 0, value: 1, generic: 2 }
            -
                oid: otherStateSensorTable
                value: otherStateSensorErrorStatus
                num_oid: .1.3.6.1.4.1.5528.100.4.2.10.1.3.
                descr: otherStateSensorLabel
                index: '{{ $index }}'
                state_name: otherStateSensorErrorStatus
                states:
                    - { descr: normal, graph: 0, value: 0, generic: 0 }
                    - { descr: info, graph: 0, value: 1, generic: 1 }
                    - { descr: warning, graph: 0, value: 2, generic: 1 }
                    - { descr: error, graph: 0, value: 3, generic: 2 }
                    - { descr: critical, graph: 0, value: 4, generic: 2 }
                    - { descr: failure, graph: 0, value: 5, generic: 2 }

```

For advanced state discovery:

This example will be based on a Cisco power supply sensor and is all it takes to have sensor state support for Cisco power supplys in Cisco switches.
The file should be located in /includes/discovery/sensors/state/cisco.inc.php.

```php
<?php

if ($device['os_group'] == 'cisco') {
    $oids = snmpwalk_cache_multi_oid($device, 'ciscoEnvMonSupplyStatusTable', array(), 'CISCO-ENVMON-MIB');
    $cur_oid = '.1.3.6.1.4.1.9.9.13.1.5.1.3.';

    if (is_array($oids)) {

        //Create State Index
        $state_name = 'ciscoEnvMonSupplyState';
        $state_index_id = create_state_index($state_name);

        //Create State Translation
        if ($state_index_id) {
            $states = array(
                 array($state_index_id,'normal',0,1,0) ,
                 array($state_index_id,'warning',0,2,1) ,
                 array($state_index_id,'critical',0,3,2) ,
                 array($state_index_id,'shutdown',0,4,3) ,
                 array($state_index_id,'notPresent',0,5,3) ,
                 array($state_index_id,'notFunctioning',0,6,2)
             );
            foreach($states as $value){ 
                $insert = array(
                    'state_index_id' => $value[0],
                    'state_descr' => $value[1],
                    'state_draw_graph' => $value[2],
                    'state_value' => $value[3],
                    'state_generic_value' => $value[4]
                );
                dbInsert($insert, 'state_translations');
            }
        }

        foreach ($oids as $index => $entry) {
            //Discover Sensors
            discover_sensor($valid['sensor'], 'state', $device, $cur_oid.$index, $index, $state_name, $entry['ciscoEnvMonSupplyStatusDescr'], '1', '1', null, null, null, null, $entry['ciscoEnvMonSupplyState'], 'snmp', $index);

            //Create Sensor To State Index
            create_sensor_to_state_index($device, $state_name, $index);
        }
    }
}
```
