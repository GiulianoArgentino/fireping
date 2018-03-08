<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 5/01/2018
 * Time: 21:51
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Slave;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class SlaveFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $slave = new Slave();
        $slave->setId("slave1");
        $slave->setLastContact(new \DateTime());
        $slave->setSlaveGroup($this->getReference('slavegroup-1'));
        $manager->persist($slave);
        $this->addReference('slave-1', $slave);

        $slave = new Slave();
        $slave->setId("slave-unused");
        $slave->setLastContact(new \DateTime());
        $slave->setSlaveGroup($this->getReference('slavegroup-unused'));
        $manager->persist($slave);
        $this->addReference('slave-unused', $slave);

        $manager->flush();
    }

    public function getDependencies()
    {
        return array(
            SlaveGroupFixtures::class,
        );
    }
}