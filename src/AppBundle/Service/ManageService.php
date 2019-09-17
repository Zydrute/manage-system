<?php

namespace AppBundle\Service;

use AppBundle\Entity\User;
use AppBundle\Entity\Groups;
use Doctrine\ORM\EntityManager;

class ManageService
{
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        
    }
    
    public function getUsers(){
        $users = $this->em->getRepository('AppBundle:User')->findAll();
        return $users;
    }

    public function getGroups(){
        $groups = $this->em->getRepository('AppBundle:Groups')->findAll();
        return $groups;
    }
}