<?php

namespace App\Controller;

use App\Repository\PostRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/default')]
class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(PostRepository $postRepo): Response
    {

        $posts = $postRepo->findAll();
        
        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController',
            'posts'=>$posts,
        ]);

    }

}
