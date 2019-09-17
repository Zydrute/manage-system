<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class UserListType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('username', EntityType::class, array(
            'class' => 'AppBundle:User',
            'required' => false,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('u')
                ->orderBy('u.username', 'ASC');
            }, 
            'placeholder' => 'Add user..',         
            'choice_label' => function ($user) {
                return $user->getUserName();
            }
        ))
        ->add('submit', SubmitType::class);
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return 'appbundle_user';
    }
}