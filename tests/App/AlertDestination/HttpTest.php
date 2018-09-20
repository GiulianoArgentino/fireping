<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 8/03/2018
 * Time: 20:39
 */

namespace Tests\App\AlertDestination;

use App\AlertDestination\Http;
use App\AlertDestination\Monolog;
use App\Entity\Alert;
use App\Entity\AlertRule;
use App\Entity\Device;
use App\Entity\SlaveGroup;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class HttpTest extends TestCase
{
    public function testNoArguments()
    {
        $guzzle = $this->prophesize('GuzzleHttp\\Client');
        $guzzle->post("url", Argument::any())->shouldBeCalledTimes(0);
        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');

        $http = new Http($guzzle->reveal(), $logger->reveal());

        $device = new Device();
        $device->setName('device');
        $slaveGroup = new SlaveGroup();
        $slaveGroup->setName('group');
        $alertRule = new AlertRule();
        $alertRule->setName('rule');
        $alert = new Alert();
        $alert->setDevice($device);
        $alert->setSlaveGroup($slaveGroup);
        $alert->setAlertRule($alertRule);

        $http->trigger($alert);
        $http->clear($alert);
    }

    public function testException()
    {
        $guzzle = $this->prophesize('GuzzleHttp\\Client');
        $guzzle->post("url", Argument::any())->shouldBeCalledTimes(2)->willThrow(new \Exception('test'));
        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $logger->error(Argument::type('string'))->shouldBeCalledTimes(2);

        $http = new Http($guzzle->reveal(), $logger->reveal());
        $http->setParameters(array('url' => 'url'));

        $device = new Device();
        $device->setName('device');
        $slaveGroup = new SlaveGroup();
        $slaveGroup->setName('group');
        $alertRule = new AlertRule();
        $alertRule->setName('rule');
        $alert = new Alert();
        $alert->setDevice($device);
        $alert->setSlaveGroup($slaveGroup);
        $alert->setAlertRule($alertRule);

        $http->trigger($alert);
        $http->clear($alert);
    }

    public function testTrigger()
    {
        $guzzle = $this->prophesize('GuzzleHttp\\Client');
        $guzzle->post("url", Argument::any())->shouldBeCalledTimes(1);
        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');

        $http = new Http($guzzle->reveal(), $logger->reveal());
        $http->setParameters(array('url' => 'url'));

        $device = new Device();
        $device->setName('device');
        $slaveGroup = new SlaveGroup();
        $slaveGroup->setName('group');
        $alertRule = new AlertRule();
        $alertRule->setName('rule');
        $alert = new Alert();
        $alert->setDevice($device);
        $alert->setSlaveGroup($slaveGroup);
        $alert->setAlertRule($alertRule);

        $http->trigger($alert);
    }

    public function testClear()
    {
        $guzzle = $this->prophesize('GuzzleHttp\\Client');
        $guzzle->post("url", Argument::any())->shouldBeCalledTimes(1);
        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');

        $http = new Http($guzzle->reveal(), $logger->reveal());
        $http->setParameters(array('url' => 'url'));

        $device = new Device();
        $device->setName('device');
        $slaveGroup = new SlaveGroup();
        $slaveGroup->setName('group');
        $alertRule = new AlertRule();
        $alertRule->setName('rule');
        $alert = new Alert();
        $alert->setDevice($device);
        $alert->setSlaveGroup($slaveGroup);
        $alert->setAlertRule($alertRule);

        $http->clear($alert);
    }
}