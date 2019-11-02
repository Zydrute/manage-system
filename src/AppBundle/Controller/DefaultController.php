<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Service\ManageService;
use AppBundle\Entity\User;
use AppBundle\Entity\GroupsLogs;
use AppBundle\Form\CreateGroupType;
use AppBundle\Form\UserType;
use AppBundle\Form\UserListType;
use AppBundle\Entity\Groups;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {

        return $this->render('default/index.html.twig', array(
            'base_dir' => realpath($this->container->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ));
    }

    /**
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/manage", name="manage")
     */
    public function manageAction()
    {
        $manageService = $this->get('manage_service');
        return $this->render('AppBundle::manage.html.twig', array(
            'users' => $manageService->getUsers(), 'groups' => $manageService->getGroups()));
    }

    /**
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/add-user", name="add_user")
     */
    public function addUserAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $password = $this->get('security.password_encoder')
                ->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);
            $em->persist($user);
            $em->flush();
 
            return $this->redirectToRoute('manage');
        }
        return $this->render('AppBundle::addUser.html.twig',
        array('form' => $form->createView()));
    }

    /**
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/delete-user/{id}", name="delete_user")
     */
    public function deleteUserAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('AppBundle:User')->find($id);
        $groupLog = $em->getRepository('AppBundle:GroupsLogs')->findByUserId($user->getId());

        if($groupLog == null){
            $em->remove($user);
            $em->flush();
        }else{
            $request->getSession()
            ->getFlashBag()
            ->add('not-success', 'User is in a group. First, please remove user from a group!');
        }
        return $this->redirectToRoute ('manage');
    }

    /**
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/add-group", name="add_group")
     */
    public function addGroupAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $form = $this->createForm(new CreateGroupType());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $group = new Groups();
            $data = $form->getData();
            $group->setName($data->getName());
            $em->persist($group);
            $em->flush();
            return $this->redirectToRoute('manage');
        }

        return $this->render('AppBundle::addGroup.html.twig', array(
            'form' => $form->createView()));
    }

    /**
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/delete-group/{id}", name="delete_group")
     */
    public function deleteGroupAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $usersInGroup = $em->getRepository('AppBundle:GroupsLogs')->findUsersIdsByGroupId($id);
        if($usersInGroup == null){
            $group = $em->getRepository('AppBundle:Groups')->findById($id);
            $em->remove($group[0]);
            $em->flush();
            return $this->redirectToRoute('manage');
        }else{
            $request->getSession()
            ->getFlashBag()
            ->add('message', 'Group has users!First, delete users.');
        }
        
        return $this->render('AppBundle::deleteGroup.html.twig');
    }

    /**
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/add-user-into-group/{id}", name="add_user_into_group")
     */
    public function addUserIntoGroupAction($id, Request $request)
    {
        $form = $this->createForm(UserListType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $em = $this->getDoctrine()->getManager();
            $user = $form->getData();
            $userId = $user['username']->getId();
            $userInGroup = $em->getRepository('AppBundle:GroupsLogs')->findGroupLog($userId, $id);
            if($userInGroup == null){
                $groupLog = new GroupsLogs();
                $groupLog->setUserId($userId);
                $groupLog->setGroupId($id);
                $em->persist($groupLog);
                $em->flush();
                return $this->redirectToRoute('manage');
            }else{
                $request->getSession()
                ->getFlashBag()
                ->add('message-group', 'User is a part of this group!');
            }           
        }
        return $this->render('AppBundle::addUserIntoGroup.html.twig', array(
            'form' => $form->createView()));
    }

    /**
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/delete-user-from-group/{id}", name="delete_user_from_group")
     */
    public function deleteUserFromGroupAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $usersIds = $em->getRepository('AppBundle:GroupsLogs')->findUsersIdsByGroupId($id);

        if ($usersIds != null){
            $usersIdsArray = array();
            foreach($usersIds as $item){
                array_push($usersIdsArray, $item['userId']);
            }
            $users = $em->getRepository('AppBundle:User')->findUsersByIds($usersIdsArray);
        }else{
            $users = null;
        }

        return $this->render('AppBundle::deleteUserFromGroup.html.twig', array(
            'users' => $users, 'groupId' => $id));
    }

    /**
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/delete-user-from-group-submit/{groupId}/{userId}", name="delete_user_from_group_submit")
     */
    public function deleteUserFromGroupSubmitAction($groupId, $userId, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $groupLog = $em->getRepository('AppBundle:GroupsLogs')->findGroupLog($userId, $groupId);
        if($groupLog != null){
            $em->remove($groupLog[0]);
            $em->flush();
        }else{
            $request->getSession()
                ->getFlashBag()
                ->add('error', 'Error!');
        }
        return $this->redirectToRoute ('manage');
       
    }

}
