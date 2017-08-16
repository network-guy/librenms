<?php
/**
 * YamlTest.php
 *
 * -Description-
 *
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    LibreNMS
 * @link       http://librenms.org
 * @copyright  2016 Neil Lathwood
 * @author     Neil Lathwood <librenms+n@laf.io>
 */

namespace LibreNMS\Tests;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use PHPUnit_Framework_ExpectationFailedException as PHPUnitException;

class YamlTest extends \PHPUnit_Framework_TestCase
{

    public function testOSYaml()
    {
        global $config;

        $pattern = $config['install_dir'] . '/includes/definitions/*.yaml';
        foreach (glob($pattern) as $file) {
            try {
                $data = Yaml::parse(file_get_contents($file));
            } catch (ParseException $e) {
                throw new PHPUnitException("$file Could not be parsed");
            }

            $this->assertArrayHasKey('os', $data, $file);
            $this->assertArrayHasKey('type', $data, $file);
            $this->assertArrayHasKey('text', $data, $file);
        }
    }

    public function testDiscoveryYaml()
    {
        global $config;

        $pattern = $config['install_dir'] . '/includes/definitions/discovery/*.yaml';
        foreach (glob($pattern) as $file) {
            try {
                $data = Yaml::parse(file_get_contents($file));
            } catch (ParseException $e) {
                throw new PHPUnitException("$file Could not be parsed");
            }

            foreach ($data['modules'] as $module => $sub_modules) {
                foreach ($sub_modules as $type => $sub_module) {
                    foreach ($sub_module['data'] as $sensor) {
                        $this->assertArrayHasKey('oid', $sensor, $file);
                        if ($type !== 'pre-cache') {
                            $this->assertArrayHasKey('oid', $sensor, $file);
                            $this->assertArrayHasKey('num_oid', $sensor, $file);
                            $this->assertArrayHasKey('descr', $sensor, $file);
                        }
                    }
                }
            }
        }
    }
}
