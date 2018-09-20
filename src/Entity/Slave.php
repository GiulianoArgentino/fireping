<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Slave
 *
 * @ORM\Table(name="slave")
 * @ORM\Entity(repositoryClass="App\Repository\SlaveRepository")
 * @ApiResource(attributes={"normalization_context"={"groups"={"slave"}}})
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class Slave
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", length=255)
     * @ORM\Id
     * @Groups({"slave"})
     */
    private $id;

    /**
     * @var SlaveGroup|null
     *
     * @ORM\ManyToOne(targetEntity="SlaveGroup", inversedBy="slaves")
     * @ORM\JoinColumn(name="slavegroup_id", referencedColumnName="id")
     * @Groups({"slave"})
     */
    private $slavegroup;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_contact", type="datetime")
     * @Groups({"slave"})
     */
    private $lastContact;

    /**
     * Set id
     *
     * @param string $id
     *
     * @return Slave
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set lastContact
     *
     * @param \DateTime $lastContact
     *
     * @return Slave
     */
    public function setLastContact($lastContact)
    {
        $this->lastContact = $lastContact;

        return $this;
    }

    /**
     * Get lastcontact
     *
     * @return \DateTime
     */
    public function getLastContact()
    {
        return $this->lastContact;
    }

    /**
     * Set slavegroup
     *
     * @param \App\Entity\SlaveGroup $slavegroup
     *
     * @return Slave
     * @Groups({"slave"})
     */
    public function setSlaveGroup(\App\Entity\SlaveGroup $slavegroup = null)
    {
        $this->slavegroup = $slavegroup;

        return $this;
    }

    /**
     * Get slavegroup
     *
     * @return \App\Entity\SlaveGroup|null
     * @Groups({"slave"})
     */
    public function getSlaveGroup()
    {
        return $this->slavegroup;
    }

    /*
     * toString
     */
    public function __toString()
    {
        return $this->id;
    }
}