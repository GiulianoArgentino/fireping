<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Device;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class SearchController extends Controller
{
    /**
     * @Route("/search")
     */
    public function indexAction(Request $request, EntityManagerInterface $em)
    {
        $q = $request->get('q');

        if (!$q || $q == "") {
            return $this->redirect("/");
        }

        $searchDevices = $em->createQuery("
            SELECT d
            FROM AppBundle:Device d
            WHERE d.name LIKE '%".$q."%'
            OR d.ip LIKE '%".$q."%'
        ")->getResult();

        $searchDomains = $em->createQuery("
            SELECT d
            FROM AppBundle:Domain d
            WHERE d.name LIKE '%".$q."%'
        ")->getResult();

        $domains = $em->getRepository("AppBundle:Domain")->findBy(array('parent' => null), array('name' => 'ASC'));

        return $this->render('search/index.html.twig', array(
            'q' => $q,
            'domains' => $domains,
            'search_devices' => $searchDevices,
            'search_domains' => $searchDomains
        ));
    }
}