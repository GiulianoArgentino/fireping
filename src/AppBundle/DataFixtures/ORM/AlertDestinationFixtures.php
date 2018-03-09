<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 8/03/2018
 * Time: 19:32
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\AlertDestination;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class AlertDestinationFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $alertDestination = new AlertDestination();
        $alertDestination->setName('syslog');
        $alertDestination->setType('syslog');
        $alertDestination->setParameters(array());
        $manager->persist($alertDestination);

        $manager->flush();

        $this->addReference('alertdestination-1', $alertDestination);
    }
}