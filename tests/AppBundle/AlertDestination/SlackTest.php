<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 8/03/2018
 * Time: 20:39
 */

namespace Tests\AppBundle\AlertDestination;

use AppBundle\AlertDestination\Monolog;
use AppBundle\AlertDestination\Slack;
use AppBundle\Entity\Alert;
use AppBundle\Entity\AlertRule;
use AppBundle\Entity\Device;
use AppBundle\Entity\SlaveGroup;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class SlackTest extends TestCase
{
    public function testNoArguments()
    {
        $client = $this->prophesize('CL\\Slack\\Transport\\ApiClient');
        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');

        $slack = new Slack($client->reveal(), $logger->reveal());

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

        $this->assertEquals(false, $slack->trigger($alert));
        $this->assertEquals(false, $slack->clear($alert));
    }

    public function testException()
    {
        $token = 'abc123';

        $client = $this->prophesize('CL\\Slack\\Transport\\ApiClient');
        $client->send(Argument::any(), $token)->shouldBeCalledTimes(2)->willThrow(new \Exception('test'));
        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $logger->error(Argument::type('string'))->shouldBeCalledTimes(2);

        $slack = new Slack($client->reveal(), $logger->reveal());
        $slack->setParameters(array('token' => $token, 'channel' => 'general'));

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

        $slack->trigger($alert);
        $slack->clear($alert);
    }

    public function testTrigger()
    {
        $token = 'abc123';

        $client = $this->prophesize('CL\\Slack\\Transport\\ApiClient');
        $client->send(Argument::any(), $token)->shouldBeCalledTimes(1);
        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');

        $slack = new Slack($client->reveal(), $logger->reveal());
        $slack->setParameters(array('token' => $token, 'channel' => 'general'));

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

        $slack->trigger($alert);
    }

    public function testClear()
    {
        $token = 'abc123';

        $client = $this->prophesize('CL\\Slack\\Transport\\ApiClient');
        $client->send(Argument::any(), $token)->shouldBeCalledTimes(1);
        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');

        $slack = new Slack($client->reveal(), $logger->reveal());
        $slack->setParameters(array('token' => $token, 'channel' => 'general'));

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

        $slack->clear($alert);
    }
}