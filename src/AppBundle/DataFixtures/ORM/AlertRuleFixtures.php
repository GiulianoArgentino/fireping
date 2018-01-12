<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 5/01/2018
 * Time: 21:51
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\AlertRule;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class AlertRuleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $alertrule = new AlertRule();
        $alertrule->setName('Alertrule 1');
        $alertrule->setDatasource('loss');
        $alertrule->setPattern(">0,>0");
        $alertrule->setProbe($this->getReference('probe-ping'));
        $manager->persist($alertrule);

        $manager->flush();

        $this->addReference('alertrule-1', $alertrule);
    }

    public function getDependencies()
    {
        return array(
            ProbeFixtures::class,
        );
    }
}