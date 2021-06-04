<?php


namespace App\Controller;


use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class mainController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{

    /**
     * @Route("/", name="main_home")
     */
    public function home(){

        $user = $this->getUser();

        if($user){
            return $this->redirectToRoute('sortie_index');
        }else{
            return $this->redirectToRoute('app_login');
        }
    }
}