<?php

namespace App\Controller;

use App\Service\JWTService;
use App\Service\SendMailService;
use App\Repository\UserRepository;
use App\Form\ResetPasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ResetPasswordRequestFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/connexion', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/deconnexion', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }


    #[Route(path: '/oubli-pass', name: 'app_forgotten_password')]
    public function forgottenPassword(Request $request, UserRepository $userRepo, JWTService $jwt, TokenGeneratorInterface $tokenGeneratorInterface, EntityManagerInterface $em, SendMailService $mail): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);

        // Récupère les datas du form
        $form->handleRequest($request);
        // dd($form);

        // Vérification des données
        if($form->isSubmitted() && $form->isValid()){
            //On va chercher l'user par email
            $user = $userRepo->findOneByEmail($form->get('email')->getData());
            // dd($user);

            //Vérification si user existe
            if($user){
                //Génère un token de réinitialisation
                //Avec le service créé
                // Création du header
                // $header = [
                //     'typ' => 'JWT',
                //     'alg' => 'HS256'
                // ];
                // //Création de payload avec le user Id
                // $payload = [
                //     'user_id' => $user->getId()
                // ];
                // Générer le token
                // $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));
// dd($token);
// dd(base64_encode($this->getParameter('app.jwtsecret'))); // =>"MGhMYTgzbGxlQnJvdWUxMWUhOw=="
                //Avec token généré automatiquement
                $token = $tokenGeneratorInterface->generateToken();
                // dd($token);

                $user->setResetToken($token);
            //     //on peut rajouter des try and catch
                $em->persist($user);
                $em->flush();

            // // Génération d'un lien de réinitialisation du MDP
            // //Il faut créer une new route 
            // // Générer une URL
            $url = $this->generateUrl('app_reset_pass', ['token'=> $token], UrlGeneratorInterface::ABSOLUTE_URL);
            // // dd($url);

            // //Création les données du mail
            // $context =[
            //     'url'=> $url,
            //     'user'=> $user
            // ];
            // ou 
            $context = compact('url', 'user');

            // //Envoyer le mail
                $mail->send(
                    'no-reply@monsite.fr',
                    $user->getEmail(),
                    'Réinitialisation du Mot de Passe',
                    'password_reset',
                    $context,
                );
            //     //On peut vérifier les erreur ici try & catch

            //     //Cela fonctionne
                $this->addFlash('success', 'Email envoyé avec succès');
                return $this->redirectToRoute('app_login');
            }

            //Pas de user ou null
            $this->addFlash('danger', 'Un problème est survenu');
            return $this->redirectToRoute('app_login');
            
        }

        return $this->render('security/reset_password_request.html.twig',[
            'requestPassForm' => $form->createView(),
        ]);
        

    }


// ICI J AI UN SOUCIS MON JETON EST INVALIDE 50:00
    #[Route('/oubli-pass/{token}', name:'app_reset_pass')]
    public function resetPass(string $token, Request $request, UserRepository $userRepo, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        //Vérifier si on a ce token ds la base de données
        $user = $userRepo->findOneByResetToken($token);
        // dd($user); // si existe on recupère les données du user sinon null

        if($user){
             // créer un form/ResetPasswordFormType.php
            $form = $this->createForm(ResetPasswordFormType::class);

//             // Récupère les datas du form
            $form->handleRequest($request);
//             // dd($form);

//             // Vérification des données
            if ($form->isSubmitted() && $form->isValid()){
                //dd($form->get('password')->getData());
                //On efface le token 
                $user->setResetToken('');
                $user->setPassword(
                    $passwordHasher->hashPassword(
                        $user,
                        $form->get('password')->getData()
                    )
                );
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Mot de Passe changé avec succès');
                return $this->redirectToRoute('app_login');
            }

            return $this->render('security/reset_password.html.twig', [
                'passForm'=> $form->createView(),
            ]);

        }
        $this->addFlash('danger', 'Jeton invalide');
        return $this->redirectToRoute('app_login');
    }
}
