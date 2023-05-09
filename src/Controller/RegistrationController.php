<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\UserAuthenticator;
use App\Service\JWTService;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, UserAuthenticator $authenticator, EntityManagerInterface $entityManager, SendMailService $mail, JWTService $jwt): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();
            
    //Génération le JWT de l'user
        //Injecter le service ds la class
            //Création du header
            $header = [
                'typ'=> 'JWT',
                'alg'=> 'HS256'
            ];

            //Création de payload avec le user Id
            $payload = [
                'user_id'=> $user->getId()
            ];

            // Générer le token
            $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));

            //Vérification si token ok
            //Récupérer le secret encoder et le coller sur jwt.io
            // dd(base64_encode($this->getParameter('app.jwtsecret')));
            // Créer un new User et coller le token sur jwt.io
            // dd($token);

            // do anything else you need here, like send an email
            //On envoie un mail + token
            $mail->send(
                'no-reply@monsite.fr',
                $user->getEmail(),
                'activation de votre compte sur le site monSite',
                'register',
                [
                    'user'=> $user,
                    'token'=> $token
                ],
                // Ou
                // compact('user', 'token')
            );


            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verif/{token}', name: 'verify_user')]
    public function verifUser($token, JWTService $jwt, UserRepository $userRepo, EntityManagerInterface $em): Response
    {
        // Etape1 : Créer un newUser et copier le token
        // dd($token);

        // Etape2 : Rafraichir la page du cliq de mailtrap
        // dd($jwt->isValid($token)); // si true ok

        //Etape3 : Récupère le payload
        //Rafraichir la page du cliq de mailtrap
        //Doit voir un array avec nos données
        // dd($jwt->getPayload($token));

        //Etape 4 : Vérification si Token valide ds le tps
        // dd($jwt->isExpired($token)); // si false token valide

        //Etape 5 : Récupération du Header
        // dd($jwt->getHeader($token)); // 

        //Etape 6 : Vérification de la signature du token
        // dd($jwt->check($token, $this->getParameter('app.jwtsecret'))); // true => Mon token n'a pas été corrompu



        //On Vérifie si token est valide et n'a pas expiré et ni modifier
        // isExpired return false donc mettre !devant
        if($jwt->isValid($token) && !$jwt->isExpired($token) && $jwt->check($token, $this->getParameter('app.jwtsecret'))){

            // Récupère le payload de mon token
            $payload = $jwt->getPayload($token);

            //Récupere le user du token
            $user = $userRepo->find($payload['user_id']);

            // Vérification que le user existe et n'a pas encore vérifier son compte
            if($user && !$user->getIsVerified()){
                $user->setIsVerified(true);

                $em->flush($user);

                $this->addFlash('success', 'Vous êtes activé');
                
                return $this->redirectToRoute('app_profil');

            }
        }
        // Ici un probleme se pose dans le token
        $this->addFlash('danger', 'Le token est invalidé ou expiré');
        return $this->redirectToRoute('app_login');
    }


    #[Route('/renvoiverif', name: 'rescend_verif')]
    public function rescendVerif(JWTService $jwt, SendMailService $mail, UserRepository $userRepo): Response
    {
        /**
         * @var User $user 
         */
        //Récupérer le user connecté
        $user = $this->getUser();
        //Vérifie si connecté
        if(!$user){
            $this->addFlash('danger', 'Vous devez être connecté pour accéder à cette page');
            return $this->redirectToRoute('app_login');
        }
        //Vérifie si déjà vérifier
        if($user->getIsVerified()){
            $this->addFlash('warning', 'Vous êtes déjà activé');
            return $this->redirectToRoute('app_profil');
        }

        //Création du header
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];

        //Création de payload avec le user Id
        $payload = [
            'user_id' => $user->getId()
        ];

        // Générer le token
        $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));

        // Renvoyer le mail
        $mail->send(
            'no-reply@monsite.fr',
            $user->getEmail(),
            'activation de votre compte sur le site monSite',
            'register',
            [
                'user' => $user,
                'token' => $token
            ],
            // Ou
            // compact('user', 'token')
        );
        $this->addFlash('success', 'Email de vérification envoyé');
        return $this->redirectToRoute('app_profil');
    }
        


}
