<?php

namespace App\Controller;

use App\Entity\Domain;
use App\Repository\AlertRepository;
use App\Repository\DomainRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class AlertController extends Controller
{
    /**
     * @param AlertRepository $alertRepository
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/alerts")
     */
    public function indexAction(AlertRepository $alertRepository)
    {
        $alertDomains = array();
        $alerts = $alertRepository->findBy(array('active' => 1));
        foreach($alerts as $alert) {
            if (!isset($alertDomains[$alert->getDevice()->getRootDomain()->getId()])) {
                $alertDomains[$alert->getDevice()->getRootDomain()->getId()] = array('name' => $alert->getDevice()->getRootDomain()->getName(), 'alerts' => 0);
            }
            $alertDomains[$alert->getDevice()->getRootDomain()->getId()]['alerts']++;
        }

        return $this->render('alert/index.html.twig', array(
            'alertDomains' => $alertDomains,
            'alerts' => $alerts,
        ));
    }

    /**
     * @param Domain $domain
     * @param DomainRepository $domainRepository
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/alerts/domain/{id}")
     * @ParamConverter("domain", class="App:Domain")
     */
    public function domainAction(Domain $domain, DomainRepository $domainRepository)
    {
        $alertDomains = $domainRepository->findBy(array('parent' => $domain));

        return $this->render('alert/domain.html.twig', array(
            'domain' => $domain,
            'alertDomains' => $alertDomains,
        ));
    }
}