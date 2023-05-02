<?php

namespace App\Controller;

use App\Entity\Post;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpParser\Node\Stmt\TryCatch;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class ApiPostController extends AbstractController
{
    public function __construct(
        private PostRepository $postRepo,
        private SerializerInterface $serializer,
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
    )
    {
        
    }
    #[Route('/post', name: 'api_post_get', methods:"GET")]
    public function getPost(): Response
    {
        // $posts = $postRepo->findAll();
        // $json = $serializer->serialize($posts, 'json');
        // $response = new JsonResponse($json, 200, [], true);
        // $response = ;
        return $this->json($this->postRepo->findAll(), 200, []);
    }


    #[Route('/post', name: 'api_post_set', methods:"POST")]
    public function setPost(Request $request)
    {
        $json = $request->getContent();
        // dd($json);
try{

        // Transformer le json en entity(array associatif ou en objet class)
        // Désérialization
        // deserialize($lesDatas, en quoi, 'format que l'on recoit')
        $post = $this->serializer->deserialize($json, Post::class, 'json');

        $post->setIsPublished(false);

        //Tjs vérifier les datas avant envoi
        $errors = $this->validator->validate($post);

        // Si error un array en json avec le message
        if(count($errors)> 0){
            return $this->json($errors, 400);
        };

        //On envoit à la BDD
        $this->em->persist($post);
        $this->em->flush();
        // dd($post); 

        //On voudrait une autre response lors de la création
        //Bonne Pratique => on donne à Postman 
            // - Response en json avec les data du nouvel article pour qu'il puisse récupérer le new id 
            // - Un statut http 200 pour ok

        return $this->json($post, 201, []);
        // return $this->json($post, 201, [], ['groups'=> 'post:nom']);
} catch(NotEncodableValueException $e){

    // Transformer en json l erreur
    return $this->json([
        // Faire une response personnalisé en json en array associatif avec son status et le message
        'status' => 400,
        'message'=> $e->getMessage(),
        // 'message'=> 'erreur syntaxe'
    ], 400);
}
    }


    #[Route('/post/{id<\d+>}', name: 'api_post_delete', methods: "DELETE")]
    public function deletePost(int $id): Response
    {
        $post = $this->postRepo->find($id);
    if (is_null($post)){
        throw $this->createNotFoundException('Article non trouvé !!');
    }
    $this->em->remove($post);
    $this->em->flush();
    // $json = $serializer->serialize($posts, 'json');
    // $response = new JsonResponse($json, 200, [], true);
    // $response = ;
    return $this->json($post, 204, []);

    }


    #[Route('/post/{id<\d+>}', name: 'api_post_put', methods: "PUT")]
    public function editPost(int $id, Request $request): Response
    {
        $post = $this->postRepo->find($id);
        // Envoi du code d'erreur HTTP 404 si la catégorie n'a pas été trouvée.
        if (is_null($post)) {
            throw $this->createNotFoundException('Article non trouvée');
        }

        // Récupère les datas de la request
        $datas = json_decode($request->getContent(), true);
        // dd($datas);
        //Modification
        $post->setTitle($datas['title']);
        $post->setAuthor($datas['author']);
        $post->setContent($datas['content']);
        $post->setIspublished($datas['isPublished']);

        $this->em->persist($post);
        $this->em->flush();

        $json= $this->serializer->serialize($post, 'json');

        $response = new Response($json, 200, [], true);

        return $response;


    }



/**
 * Récupère les article publié
 *
 * @param PostRepository $postRepo
 * @param SerializerInterface $serializer
 * @return Response
 */
    #[Route('/published', name: 'api_post_published', methods: "GET")]
    public function publishedPost(): Response
    {
        $posts = $this->postRepo->findBy(['ispublished' => true]);
        $json = $this->serializer->serialize($posts, 'json');
        $response = new JsonResponse($json, 200, [], true);
    return $response;       
     // return $this->json($postRepo->findAll(), 200, []);
    }


/**
 * Display post by id
 */
    #[Route('/post/{id<\d+>}', name: 'api_post_id', methods: "GET")]
    public function getPostId(int $id): Response
    {
        $posts = $this->postRepo->find($id);
        $json = $this->serializer->serialize($posts, 'json');
        $response = new JsonResponse($json, 200, [], true);
    return $response;       
     // return $this->json($postRepo->findAll(), 200, []);
    }
}






