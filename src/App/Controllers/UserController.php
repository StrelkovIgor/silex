<?php

namespace App\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type as Type;
use Symfony\Component\Form\FormError;
use App\Entity\User;

class UserController
{	
	public function loginAction(Request $request, Application $app)
    {
        $form = $app['form.factory']->createBuilder(Type\FormType::class)
            ->add('username', Type\TextType::class, array('label' => 'Username'))
            ->add('password', Type\PasswordType::class, array('label' => 'Password'))
            ->add('login', Type\SubmitType::class)
            ->getForm();
			
        $data = array(
            'form'  => $form->createView(),
            'error' => $form->getErrors(),
        );
        return $app['twig']->render('user/login.twig', $data);
    }
	
	public function regAction(Request $request, Application $app)
	{
		$user = new User();
		$form = $app['form.factory']->createBuilder(Type\FormType::class,$user)
			->add('username', Type\TextType::class)
			->add('mail', Type\EmailType::class, [
				'required' => true
			])
			->add('password', Type\RepeatedType::class, [
				'type' => Type\PasswordType::class
			])
            ->add('save', Type\SubmitType::class)
			->getForm();
			
		if($request->isMethod('POST'))
		{
			$form->handleRequest($request);
			
			$form = $app['model.user']->isValid($user, $form);
			
			if ($form->isSubmitted() && $form->isValid())
			{
				if(count($app['service.auth']->checkUser($user->getUsername())))
				{
					$form->addError(new FormError('This user exists2'));
				}else{
					$app['model.user']->save($user);
				}
			}
		}
		
		return $app['twig']->render('user/reg.twig',['form' => $form->createView(), 'error'=>$form->getErrors()]);
	}
	
	public function serviceAction(Request $request, Application $app)
	{
		return json_encode($app['service.auth']->service($request->request->all(), $app['model.user']));
	}
}