<?php


namespace App\Controller;


use Symfony\Component\Routing\Annotation\Route;

class mainController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{

    /**
     * @Route("/", name="main_home")
     */
    public function home(){
        return $this->render('base.html.twig');
    }
}