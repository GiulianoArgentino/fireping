<?php
namespace AppBundle\DependencyInjection;

use GuzzleHttp\Client;
use AppBundle\Probe\ProbeDefinition;
use AppBundle\Probe\DeviceDefinition;
use GuzzleHttp\Exception\TransferException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProbeStore
{
    private $container;
    protected $probes = array();

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    private function addProbe(ProbeDefinition $probe)
    {
        $this->probes[] = $probe;
    }

    public function getProbes()
    {
        return $this->probes;
    }

    public function getProbeById($id)
    {
        foreach ($this->probes as $probe) {
            if ($probe->getId() === $id) {
                return $probe;
            }
        }
        return null;
    }

    public function getProbe($id, $type, $step, $samples)
    {
        foreach ($this->probes as $probe) {
            if ($probe->getId() === $id
                && $probe->getType() === $type
                && $probe->getStep() === $step
                && $probe->getSamples() === $samples) {
                return $probe;
            }
        }
        $newProbe = new ProbeDefinition($id, $type, $step, $samples);
        $this->addProbe($newProbe);
        return $newProbe;
    }

    private function deactivateAllDevices()
    {
        foreach ($this->getProbes() as $probe)
        {
            $probe->deactivateAllDevices();
        }
    }

    public function purgeAllInactiveDevices()
    {
        foreach ($this->getProbes() as $probe)
        {
            $id = $probe->getId();
            $probe->purgeAllInactiveDevices();
        }
    }

    public function sync()
    {
        $client = new Client();
        // TODO: Process Async
        $result = '';

        try {
            $id = $this->container->getParameter('slave.id');
            $result = $client->get("https://smokeping-dev.cegeka.be/api/slaves/$id/config");
        } catch (TransferException $exception) {
            // TODO: Log this failure!
        }

        $decoded = json_decode($result->getBody(), true);

        if (!$decoded) {
            // TODO: Log this failure! Something wrong with the response body.
        } else {
            $this->deactivateAllDevices();
            foreach ($decoded as $id => $probeConfig)
            {
                // TODO: More checks to make sure all of this data is here?
                $type = $probeConfig['type'];
                $step = $probeConfig['step'];
                $samples = $probeConfig['samples'];

                $probe = $this->getProbe($id, $type, $step, $samples);
                foreach ($probeConfig['targets'] as $hostname => $ip)
                {
                    $device = new DeviceDefinition($hostname, $ip);
                    $probe->addDevice($device);
                }
            }
            $this->purgeAllInactiveDevices();
        }
    }

    public function printDevices()
    {
        foreach ($this->getProbes() as $probe)
        {
            print("[Probe:".$probe->getId()."] Devices:\n");
            print_r($probe->getDevices());
        }
    }
}