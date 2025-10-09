<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Equipements;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Mailgun\Mailgun;
use Mailjet\Resources;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

final class Controller extends AbstractController
{
    #[Route('/', name: 'app_')]
    public function index(EntityManagerInterface $entityManager, Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('login');
        }
        $equipements = $entityManager->getRepository(Equipements::class)->findAll();
        return $this->render('/afficher_equipement.html.twig', [
            'controller_name' => 'Controller',
            'equipements' => $equipements,
        ]);
    }

    #[Route('/ajouter_equipement', name: 'ajouter_equipement')]
    public function ajouterEquipement(Request $request, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('login');
        }
        if ($request->isMethod('POST')) {
            $machine = $request->request->get('machine');
            $modele = $request->request->get('modele');
            $numeroSerie = $request->request->get('numero_serie');
            $statut = $request->request->get('statut');
            $etat = $request->request->get('etat');

            // Validation minimale
            if ($machine && $modele && $numeroSerie && $statut && $etat) {
                $equipement = new Equipements();
                $equipement->setMachine($machine);
                $equipement->setModele($modele);
                $equipement->setNumeroDeSerie($numeroSerie);
                $equipement->setStatut($statut);
                $equipement->setEtat($etat);

                $entityManager->persist($equipement);
                $entityManager->flush();

                $this->addFlash('success', 'Équipement ajouté avec succès.');
                return $this->redirectToRoute('app_');
            } else {
                $this->addFlash('error', 'Tous les champs sont obligatoires.');
            }
        }

        return $this->render('ajouter_equipement.html.twig');
    }

    #[Route('/supprimer_equipemnt/{id}', name: 'supprimer_equipement')]
    public function supprimerEquipement(int $id, EntityManagerInterface $entityManager, Request $request)
    {
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('login');
        }
        $equipement = $entityManager->getRepository(Equipements::class)->find($id);
        if (!$equipement) {
            $this->addFlash('error', 'Équipement non trouvé.');
            return $this->redirectToRoute('app_');
        }
        $entityManager->remove($equipement);
        $entityManager->flush();
        $this->addFlash('success', 'Équipement supprimé avec succès.');
        return $this->redirectToRoute('app_');
    }

    #[Route('/modifier_equipement/{id}', name: 'modifier_equipement')]
    public function modifierEquipement(int $id, Request $request, EntityManagerInterface $entityManager)
    {
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('login');
        }
        $equipement = $entityManager->getRepository(Equipements::class)->find($id);
        if (!$equipement) {
            $this->addFlash('error', 'Équipement non trouvé.');
            return $this->redirectToRoute('app_');
        }
        if ($request->isMethod('POST')) {
            $machine = $request->request->get('machine');
            $modele = $request->request->get('modele');
            $numeroSerie = $request->request->get('numero_serie');
            $statut = $request->request->get('statut');
            $etat = $request->request->get('etat');

            $equipement->setMachine($machine);
            $equipement->setModele($modele);
            $equipement->setNumeroDeSerie($numeroSerie);
            $equipement->setStatut($statut);
            $equipement->setEtat($etat);
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return new Response('OK');
            }

            $this->addFlash('success', 'Équipement modifié avec succès.');
            return $this->redirectToRoute('app_');
        }

        // Si GET, tu peux retourner une vue ou rien
        return new Response('Méthode non autorisée', 405);
    }

    #[Route('/login', name: 'login')]
    public function login(Request $request, EntityManagerInterface $entityManager): Response
    {
        $erreur = null;
        if ($request->isMethod('POST')) {
            $nom_utilisateur = $request->request->get('username');
            $mot_de_passe = $request->request->get('password');
            $utilisateur = $entityManager->getRepository(Utilisateur::class)->findOneBy(['nom_utilisateur' => $nom_utilisateur]);
            if (!$utilisateur) {
                $erreur = "Nom d'utilisateur  incorrect.";
            } elseif (!password_verify($mot_de_passe, $utilisateur->getMotDePasse())) {
                $erreur = " mot de passe incorrect.";
            } elseif ($utilisateur->getVerificationCode() !== null) {
                $erreur = "Votre compte n'est pas encore vérifié.";
            } else {
                // Connexion réussie : tu peux stocker l'utilisateur en session si besoin
                $request->getSession()->set('user_id', $utilisateur->getIdUtilisateur());
                return $this->redirectToRoute('app_');
            }
        }
        return $this->render('/login.html.twig', [
            'controller_name' => 'Controller',
            'erreur' => $erreur
        ]);
    }

    #[Route('/inscription', name: 'inscription')]
    public function inscription(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        
        $erreurs = [];
        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $prenom = $request->request->get('prenom');
            $email = $request->request->get('email');
            $nom_utilisateur = $request->request->get('username');
            $mot_de_passe = $request->request->get('password');

            // Vérification des champs vides
            if (!$nom) {
                $erreurs['nom'] = "Le champ nom est obligatoire.";
            }
            if (!$prenom) {
                $erreurs['prenom'] = "Le champ prénom est obligatoire.";
            }
            if (!$email) {
                $erreurs['email'] = "Le champ email est obligatoire.";
            }
            if (!$nom_utilisateur) {
                $erreurs['username'] = "Le champ nom d'utilisateur est obligatoire.";
            }
            if (!$mot_de_passe) {
                $erreurs['password'] = "Le champ mot de passe est obligatoire.";
            }

            // Validation minimale
            if ($nom && $prenom && $email && $nom_utilisateur && $mot_de_passe) {
                // Vérification unicité nom utilisateur
                $utilisateurExistant = $entityManager
                ->getRepository(Utilisateur::class)
                ->findOneBy(['nom_utilisateur' => $nom_utilisateur]);

                $emailExistant = $entityManager
                ->getRepository(Utilisateur::class)
                ->findOneBy(['email' => $email]);


                if ($utilisateurExistant) {
                    $erreurs['username'] = "Ce nom d'utilisateur est déjà pris.";
                }
                if ($emailExistant){
                    $erreurs['email'] = "Cet email est déja pris.";
                }
                if (!preg_match('/^[A-Z]/', $nom)) {
                    $erreurs['nom'] = "Le nom doit commencer par une majuscule.";
                }
        
                if (!preg_match('/^[A-Z]/', $prenom)) {
                    $erreurs['prenom'] = "Le prénom doit commencer par une majuscule.";
                }
        
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $erreurs['email'] = "Veuillez entrer une adresse email valide.";
                }
        
                if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[!@#$%^&*]).{8,}$/', $mot_de_passe)) {
                    $erreurs['password'] = "Le mot de passe doit être fort (au moins 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre, 1 symbole).";
                }
                if(empty($erreurs)) {
                    $utilisateur = new Utilisateur();
                    $utilisateur->setNom($nom);
                    $utilisateur->setPrenom($prenom);
                    $utilisateur->setEmail($email);
                    $utilisateur->setNomUtilisateur($nom_utilisateur);
                    // Générer un code de vérification à 6 chiffres
                    $verificationCode = random_int(100000, 999999);
                    $utilisateur->setVerificationCode((string)$verificationCode);
                    // Stocker la date d'expiration (30 minutes)
                    $expiration = new \DateTimeImmutable('+30 minutes');
                    $utilisateur->setVerificationCodeExpiresAt($expiration);
                    // Stocker le mot de passe hashé
                    $hashedPassword = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                    $utilisateur->setMotDePasse($hashedPassword);
                    $request->getSession()->set('user_id', $utilisateur->getIdUtilisateur());


                    $entityManager->persist($utilisateur);
                    $entityManager->flush();
                    $apiKey = $_ENV['MJ_APIKEY_PUBLIC'];
                    $apiSecret = $_ENV['MJ_APIKEY_PRIVATE'];

                    if (!$apiKey || !$apiSecret) {
                        return new Response('Configuration email manquante', 500);
                    }

                    $mj = new \Mailjet\Client($apiKey, $apiSecret, true, ['version' => 'v3.1']);

                    $body = [
                        'Messages' => [
                        [
                            'From' => [
                            'Email' => "haythem.abdellaoui100@gmail.com",
                            'Name' => "Ooredoo"
                        ],
                        'To' => [
                            [
                                'Email' => $email
                            ]
                        ],
                            'Subject' => "Code de vérification",
                            'TextPart' => "Votre code de vérification est : ",
                            'HTMLPart' => "<h3>Vérification de compte </h3><br />Bonjour " . $nom_utilisateur . ", votre code de vérification est : " . $verificationCode
                            ]
                        ]   
                    ];
                    $response = $mj->post(\Mailjet\Resources::$Email, ['body' => $body]);
                    if ($response->success()) {
                        $request->getSession()->set('email_verif', $email);
                        return $this->redirectToRoute('verification_code');
                    } else {
                        return new Response('Erreur Mailjet : ' . json_encode($response->getData()), 500);
                    }

                    
                    

                }
            } else {
                $this->addFlash('error', 'Tous les champs sont obligatoires.');
            }
        }
        return $this->render('/inscription.html.twig', [
            'controller_name' => 'Controller',
            'erreurs' => $erreurs

        ]);
    }

    #[Route('/verification_code', name: 'verification_code')]
    public function verification_code(Request $request, EntityManagerInterface $entityManager)
    {
        $erreur = null;
        if ($request->isMethod('POST')) {
            $codeSaisi = $request->request->get('verification_code');
            $email = $request->getSession()->get('email_verif'); // à adapter selon comment tu récupères l'utilisateur
            $utilisateur = $entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
            if (!$utilisateur) {
                $erreur = "Utilisateur non trouvé.";
            } elseif ($utilisateur->getVerificationCode() !== $codeSaisi) {
                $erreur = "Code incorrect";
            } elseif ($utilisateur->getVerificationCodeExpiresAt() < new \DateTimeImmutable()) {
                $erreur = "Code expiré";
            } else {
                // Code correct et non expiré : suppression des champs
                $utilisateur->setVerificationCode(null);
                $utilisateur->setVerificationCodeExpiresAt(null);
                $request->getSession()->set('user_id', $utilisateur->getIdUtilisateur());
                $entityManager->flush();
                return $this->redirectToRoute('app_');
            }
        }
        return $this->render('/verification_code.html.twig', [
            'controller_name' => 'Controller',
            'erreur' => $erreur
        ]);
    }


    

    #[Route('/mot_de_passe_oublie', name:'mot_de_passe_oublie')]
    public function mot_de_passe_oublie(Request $request, EntityManagerInterface $entityManager ){
        $erreurs = [];
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            if (!$email) {
                $erreurs['email'] = "Le champ email est obligatoire.";
            }
            if($email){
                $emailExistant = $entityManager
                ->getRepository(Utilisateur::class)
                ->findOneBy(['email' => $email]);

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $erreurs['email'] = "Veuillez entrer une adresse email valide.";
                }
                if (!$emailExistant){
                    $erreurs['email'] = "Pas de compte associé à cet email";
                }
            }
            if(empty($erreurs)){
                
                $utilisateur = $entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
                $recuperationCode = random_int(100000, 999999);
                $utilisateur->setRecuperationCode((string)$recuperationCode);
                // Stocker la date d'expiration (30 minutes)
                $expiration = new \DateTimeImmutable('+30 minutes');
                $utilisateur->setRecuperationCodeExpiresAt($expiration);
                $entityManager->persist($utilisateur);
                $entityManager->flush();

                $request->getSession()->set('email_recuperation', $email);

                $apiKey = $_ENV['MJ_APIKEY_PUBLIC'];
                $apiSecret = $_ENV['MJ_APIKEY_PRIVATE'];

                if (!$apiKey || !$apiSecret) {
                    return new Response('Configuration email manquante', 500);
                }

                $mj = new \Mailjet\Client($apiKey, $apiSecret, true, ['version' => 'v3.1']);

                $body = [
                    'Messages' => [
                    [
                        'From' => [
                        'Email' => "haythem.abdellaoui100@gmail.com",
                        'Name' => "Ooredoo"
                        ],
                        'To' => [
                            [
                                'Email' => $email
                            ]
                        ],
                            'Subject' => "Code de récupération de mot de passe",
                            'TextPart' => "Votre code de récupération de mot de passe est : ",
                            'HTMLPart' => "<h3>Récupération de mot de passe </h3><br />Bonjour , votre code de récupération est : " . $recuperationCode
                            ]
                        ]   
                    ];
                        $response = $mj->post(\Mailjet\Resources::$Email, ['body' => $body]);
                        if ($response->success()) {
                            
                        } else {
                            return new Response('Erreur Mailjet : ' . json_encode($response->getData()), 500);
                        }

                return $this->redirectToRoute('mot_de_passe_oublie2');
                
                }


        }

        return $this->render('/mot_de_passe_oublie.html.twig', [
            'controller_name' => 'Controller',
            'erreurs' => $erreurs,
        ]);
    }

    #[Route('/mot_de_passe_oublie2', name:'mot_de_passe_oublie2')]
    public function mot_de_passe_oublie2(EntityManagerInterface $entityManager, Request $request){
        
        $erreur = null;
        if ($request->isMethod('POST')) {
            $codeSaisi = $request->request->get('code_recuperation');
            $email = $request->getSession()->get('email_recuperation'); // à adapter selon comment tu récupères l'utilisateur
            $utilisateur = $entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
            if (!$utilisateur) {
                $erreur = "Utilisateur non trouvé.";
            } elseif ($utilisateur->getRecuperationCode() !== $codeSaisi) {
                $erreur = "Code incorrect";
            } elseif ($utilisateur->getRecuperationCodeExpiresAt() < new \DateTimeImmutable()) {
                $erreur = "Code expiré";
            } else {
                // Code correct et non expiré : suppression des champs
                $utilisateur->setRecuperationCode(null);
                $utilisateur->setRecuperationCodeExpiresAt(null);
                $entityManager->flush();
                $request->getSession()->set('email_changer',$email);
                return $this->redirectToRoute('changer_mot_de_passe');
            }
        }


        return $this->render('/mot_de_passe_oublie2.html.twig',[
            'controller_name'=>'Controller',
            'erreur'=>$erreur,
        ]);
    }

    #[Route('/changer_mot_de_passe', name:'changer_mot_de_passe')]
    public function changer_mot_de_passe(EntityManagerInterface $entityManager , Request $request){

        $erreur = null;
        if ($request->isMethod('POST')) {
            $mot_de_passe = $request->request->get('mot_de_passe');
            $email = $request->getSession()->get('email_recuperation'); 
            $utilisateur = $entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
            if (!$utilisateur) {
                $erreur = "Utilisateur non trouvé.";
            } if (!$mot_de_passe) {
                $erreur  = "Le champ mot de passe est obligatoire.";
            } if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[!@#$%^&*]).{8,}$/', $mot_de_passe)) {
                $erreur = "Le mot de passe doit être fort  ,Au moins 8 caractères  ,1 majuscule ,1 minuscule ,1 chiffre ,1 symbole.";
            } else {
                $hashedPassword = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                $utilisateur->setMotDePasse($hashedPassword);
                $entityManager->flush();
                $request->getSession()->set('email_changer',$email);
                return $this->redirectToRoute('login');
            }
        }

        return $this->render('changer_mot_de_passe.html.twig', [
            'controller_name' => 'Controller',
            'erreur' => $erreur,
        ]);
    }

    #[Route('/parametres', name:'parametres')]
    public function parametres(Request $request){
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('login');
        }
        return $this->render('parametres.html.twig', [
            'controller_name' => 'Controller',
        ]);
    }

    #[Route('/parametre_email', name:'parametre_email')]
    public function parametre_email(Request $request, EntityManagerInterface $entityManager){
        $erreur = null;
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('login');
        }
        $userId = $session->get('user_id');
        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($userId);
        $nom_utilisateur = $utilisateur->getNom();
 
        $email = $request->get('email');
        if (!$email){
            $erreur = "Veuillez saisir une adresse email";
        } 
        if ($email) {
            $emailExistant = $entityManager
            ->getRepository(Utilisateur::class)
            ->findOneBy(['email' => $email]);

            if ($emailExistant){
                $erreur = "Cet email est déja pris.";
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erreur = "Veuillez entrer une adresse email valide.";
            }
            if (empty($erreur)){
                $verificationCode = random_int(100000, 999999);
            $utilisateur->setVerificationCode((string)$verificationCode);
            // Stocker la date d'expiration (30 minutes)
            $expiration = new \DateTimeImmutable('+30 minutes');
            $utilisateur->setVerificationCodeExpiresAt($expiration);
            $session->set('nouveau_email',$email);

            $entityManager->persist($utilisateur);
            $entityManager->flush();
            $apiKey = $_ENV['MJ_APIKEY_PUBLIC'];
            $apiSecret = $_ENV['MJ_APIKEY_PRIVATE'];

            if (!$apiKey || !$apiSecret) {
                return new Response('Configuration email manquante', 500);
            }

            $mj = new \Mailjet\Client($apiKey, $apiSecret, true, ['version' => 'v3.1']);

            $body = [
                'Messages' => [
                    [
                        'From' => [
                        'Email' => "haythem.abdellaoui100@gmail.com",
                        'Name' => "Ooredoo"
                        ],
                        'To' => [
                            [
                                'Email' => $email
                            ]
                        ],
                            'Subject' => "Code de vérification",
                            'TextPart' => "Votre code de vérification est : ",
                            'HTMLPart' => "<h3>Vérification de l'email </h3><br />Bonjour " . $nom_utilisateur . ", votre code de vérification est : " . $verificationCode
                    ]
                    ]   
                    ];
                    $response = $mj->post(\Mailjet\Resources::$Email, ['body' => $body]);
                    if ($response->success()) {
                        return $this->redirectToRoute('parametre_email2');
                    } else {
                        return new Response('Erreur Mailjet : ' . json_encode($response->getData()), 500);
                    }
            }


        }

        
        return $this->render('parametre_email.html.twig', [
            'controller_name' => 'Controller',
            'erreur' => $erreur,
        ]);
    }


    #[Route('/parametre_email2', name:'parametre_email2')]
    public function parametre_email2(Request $request, EntityManagerInterface $entityManager){
        $erreur = null;
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('login');
        }
        $userId = $session->get('user_id');
        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($userId);
        $email = $session->get('nouveau_email');
        if ($request->isMethod('POST')) {
            $codeSaisi = $request->request->get('verification_code');
            if (!$utilisateur) {
                $erreur = "Utilisateur non trouvé.";
            } elseif ($utilisateur->getVerificationCode() !== $codeSaisi) {
                $erreur = "Code incorrect";
            } elseif ($utilisateur->getVerificationCodeExpiresAt() < new \DateTimeImmutable()) {
                $erreur = "Code expiré";
            } else {
                // Code correct et non expiré : suppression des champs
                $utilisateur->setVerificationCode(null);
                $utilisateur->setVerificationCodeExpiresAt(null);
                $utilisateur->setEmail($email);
                $entityManager->flush();
                return $this->redirectToRoute('parametres');
            }
        }
        return $this->render('parametre_email2.html.twig',[
            'controller_name'=>'Controller',
            'erreur' => $erreur,
        ]);
    }

    #[Route('/parametre_nom_utilisateur', name: 'parametre_nom_utilisateur')]
    public function parametre_nom_utilisateur(Request $request, EntityManagerInterface $entityManager){
        $erreur=null;
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('login');
        }
        $userId = $session->get('user_id');
        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($userId);
        $nom_utilisateur = $utilisateur->getNom();
        $email = $utilisateur->getEmail();
 
        $nom_utilisateur2 = $request->get('nom_utilisateur');
        if (!$nom_utilisateur2){
            $erreur = "Veuillez saisir un nom d'utilisateur";
        } 
        if ($nom_utilisateur2) {
            $nomUtilisateurExistant = $entityManager
            ->getRepository(Utilisateur::class)
            ->findOneBy(['nom_utilisateur' => $nom_utilisateur2]);

            if ($nomUtilisateurExistant){
                $erreur = "Ce nom d'utilisateur est déja pris.";
            }
            if (empty($erreur)){
            $verificationCode = random_int(100000, 999999);
            $utilisateur->setVerificationCode((string)$verificationCode);
            // Stocker la date d'expiration (30 minutes)
            $expiration = new \DateTimeImmutable('+30 minutes');
            $utilisateur->setVerificationCodeExpiresAt($expiration);
            $session->set('nouveau_nom_utilisateur',$nom_utilisateur2);

            $entityManager->persist($utilisateur);
            $entityManager->flush();
            $apiKey = $_ENV['MJ_APIKEY_PUBLIC'];
            $apiSecret = $_ENV['MJ_APIKEY_PRIVATE'];

            if (!$apiKey || !$apiSecret) {
                return new Response('Configuration email manquante', 500);
            }

            $mj = new \Mailjet\Client($apiKey, $apiSecret, true, ['version' => 'v3.1']);

            $body = [
                'Messages' => [
                    [
                        'From' => [
                        'Email' => "haythem.abdellaoui100@gmail.com",
                        'Name' => "Ooredoo"
                        ],
                        'To' => [
                            [
                                'Email' => $email
                            ]
                        ],
                            'Subject' => "Code de vérification",
                            'TextPart' => "Votre code de vérification est : ",
                            'HTMLPart' => "<h3>Vérification de l'email </h3><br />Bonjour " . $nom_utilisateur . ", votre code de vérification est : " . $verificationCode
                    ]
                    ]   
                    ];
                    $response = $mj->post(\Mailjet\Resources::$Email, ['body' => $body]);
                    if ($response->success()) {
                        return $this->redirectToRoute('parametre_nom_utilisateur2');
                    } else {
                        return new Response('Erreur Mailjet : ' . json_encode($response->getData()), 500);
                    }
            }


        }
        return $this->render('parametre_nom_utilisateur.html.twig',[
            'controller_name'=>'Controller',
            'erreur'=> $erreur,
        ]);

    }


    #[Route('/parametre_nom_utilisateur2', name: 'parametre_nom_utilisateur2')]
    public function parametre_nom_utilisateur2(Request $request, EntityManagerInterface $entityManager){
        $erreur=null;
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('login');
        }
        $userId = $session->get('user_id');
        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($userId);
        $nom_utilisateur = $session->get('nouveau_nom_utilisateur');
        if ($request->isMethod('POST')) {
            $codeSaisi = $request->request->get('verification_code');
            if (!$utilisateur) {
                $erreur = "Utilisateur non trouvé.";
            } elseif ($utilisateur->getVerificationCode() !== $codeSaisi) {
                $erreur = "Code incorrect";
            } elseif ($utilisateur->getVerificationCodeExpiresAt() < new \DateTimeImmutable()) {
                $erreur = "Code expiré";
            } else {
                // Code correct et non expiré : suppression des champs
                $utilisateur->setVerificationCode(null);
                $utilisateur->setVerificationCodeExpiresAt(null);
                $utilisateur->setNomUtilisateur($nom_utilisateur);
                $entityManager->flush();
                return $this->redirectToRoute('parametres');
            }
        }
        return $this->render('parametre_nom_utilisateur2.html.twig',[
            'controller_name' => 'Controller',
            'erreur' => $erreur,
        ]);
    }

    #[Route('/parametre_mot_de_passe', name: 'parametre_mot_de_passe')]
    public function parametre_mot_de_passe(Request $request, EntityManagerInterface $entityManager){
        $erreur = null;
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('login');
        }
        $userId = $session->get('user_id');
        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($userId);
        $nom_utilisateur = $utilisateur->getNom();
        $email = $utilisateur->getEmail();
 
        $mot_de_passe = $request->get('mot_de_passe');

        if (!$mot_de_passe){
            $erreur = "Veuillez saisir un mot de passe";
        } 
        if ($mot_de_passe) {
            if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[!@#$%^&*]).{8,}$/', $mot_de_passe)) {
                $erreur = "Le mot de passe doit être fort (au moins 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre, 1 symbole).";
            }
            if (empty($erreur)){
                $verificationCode = random_int(100000, 999999);
                $utilisateur->setVerificationCode((string)$verificationCode);
                $expiration = new \DateTimeImmutable('+30 minutes');
                $utilisateur->setVerificationCodeExpiresAt($expiration);
                $hashedPassword = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                $session->set('nouveau_mot_de_passe',$hashedPassword);

                $entityManager->persist($utilisateur);
                $entityManager->flush();
                $apiKey = $_ENV['MJ_APIKEY_PUBLIC'];
                $apiSecret = $_ENV['MJ_APIKEY_PRIVATE'];

                if (!$apiKey || !$apiSecret) {
                    return new Response('Configuration email manquante', 500);
                }

                $mj = new \Mailjet\Client($apiKey, $apiSecret, true, ['version' => 'v3.1']);

                $body = [
                    'Messages' => [
                        [
                            'From' => [
                            'Email' => "haythem.abdellaoui100@gmail.com",
                            'Name' => "Ooredoo"
                            ],
                            'To' => [
                                [
                                    'Email' => $email
                                ]
                            ],
                            'Subject' => "Code de vérification",
                            'TextPart' => "Votre code de vérification est : ",
                            'HTMLPart' => "<h3>Vérification de compte </h3><br />Bonjour " . $nom_utilisateur . ", votre code de vérification est : " . $verificationCode
                        ]
                    ]   
                ];
                $response = $mj->post(\Mailjet\Resources::$Email, ['body' => $body]);
                if ($response->success()) {
                    return $this->redirectToRoute('parametre_mot_de_passe2');
                } else {
                    return new Response('Erreur Mailjet : ' . json_encode($response->getData()), 500);
                }
            }
        }

        // Always return a Response at the end
        return $this->render('parametre_mot_de_passe.html.twig',[
            'controller_name' => 'Controller',
            'erreur' => $erreur,
        ]);
    }

    #[Route('/parametre_mot_de_passe2', name: 'parametre_mot_de_passe2')]
    public function parametre_mot_de_passe2 (Request $request, EntityManagerInterface $entityManager){
        $erreur = null;
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('login');
        }
        $userId = $session->get('user_id');
        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($userId);
        $mot_de_passe = $session->get('nouveau_mot_de_passe');
        if ($request->isMethod('POST')) {
            $codeSaisi = $request->request->get('verification_code');
            if (!$utilisateur) {
                $erreur = "Utilisateur non trouvé.";
            } elseif ($utilisateur->getVerificationCode() !== $codeSaisi) {
                $erreur = "Code incorrect";
            } elseif ($utilisateur->getVerificationCodeExpiresAt() < new \DateTimeImmutable()) {
                $erreur = "Code expiré";
            } else {
                // Code correct et non expiré : suppression des champs
                $utilisateur->setVerificationCode(null);
                $utilisateur->setVerificationCodeExpiresAt(null);
                $utilisateur->setMotDePasse($mot_de_passe);
                $entityManager->flush();
                return $this->redirectToRoute('parametres');
            }
        }
        return $this->render('parametre_mot_de_passe2.html.twig',[
            'controller_name' => 'Controller',
            'erreur' => $erreur,
        ]);

    }

    #[Route('/supprimer_compte', name: 'supprimer_compte')]
    public function supprimerCompte(Request $request, EntityManagerInterface $entityManager)
    {
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('login');
        }
        $userId = $session->get('user_id');
        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($userId);

        if (!$utilisateur) {
            $this->addFlash('error', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('parametres');
        }

        // Suppression de l'utilisateur
        $entityManager->remove($utilisateur);
        $entityManager->flush();

        // Déconnexion
        $session->invalidate();

        $this->addFlash('success', 'Votre compte a été supprimé avec succès.');
        return $this->redirectToRoute('login');
    }


    #[Route('/statistiques', name: 'statistiques')]
    public function statistiques(EntityManagerInterface $entityManager, Request $request)
    {
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('login');
        }
        $repo = $entityManager->getRepository(Equipements::class);

        $total = $repo->count([]);
        $enStock = $repo->count(['statut' => 'En stock']);
        $stockBas = $repo->count(['statut' => 'Stock bas']);
        $rupture = $repo->count(['statut' => 'Rupture']);

        // Exemple pour l'état
        $etatCounts = $entityManager->createQuery(
            'SELECT e.etat, COUNT(e.id_equipement) as nb FROM App\Entity\Equipements e GROUP BY e.etat'
        )->getResult();

        return $this->render('statistiques.html.twig', [
            'total' => $total,
            'enStock' => $enStock,
            'stockBas' => $stockBas,
            'rupture' => $rupture,
            'etatCounts' => $etatCounts,
        ]);
    }

    #[Route('/predire_panne', name: 'predire_panne')]
    public function predire_panne(Request $request, EntityManagerInterface $entityManager){
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('login');
        }
        $output = null;
        if ($request->isMethod('POST')){
            
        $data = [
            "type" => $request->request->get('type_machine'),
            "heures_utilisation" => (int)$request->request->get('heures_utilisation'),
            "temperature_moy" => (float)$request->request->get('temperature'),
            "nb_pannes_prec" => (int)$request->request->get('nbre_panne'),
            "age_en_jours" => (int)$request->request->get('age'),
            "nb_redemarrages" => (int)$request->request->get('nbre_redemarrage'),
            "charge_moy_cpu" => (float)$request->request->get('charge_cpu'),
            "nb_documents_imprimes" => (int)$request->request->get('nbre_document'),
            "marque" => $request->request->get('marque'),
            "localisation" => $request->request->get('localisation'),
            "alertes_smart" => (int)$request->request->get('nbre_alerte'),
            "nb_interventions_technicien" => (int)$request->request->get('nbre_intervention'),
        ];
        $jsonInput = json_encode($data);
        $python = 'python'; // ou chemin complet vers python
        $script = escapeshellarg(dirname(__DIR__, 2) . '/ai/predict.py');

        $cmd = "$python $script";
        $process = proc_open(
            $cmd,
            [['pipe','r'],['pipe','w'],['pipe','w']],
            $pipes
        );

        if (is_resource($process)) {
            fwrite($pipes[0], $jsonInput);
            fclose($pipes[0]);
        
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
        
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
        
            $return_value = proc_close($process);
        
            
        }
        
        }
        

        return $this->render('predire_panne.html.twig',[
            'controller_name' => 'Controller',
            'resultat_panne' => $output,
        ]);
    

    }


    #[Route('/logout', name: 'logout')]
    public function logout(Request $request)
    {
        $request->getSession()->invalidate();
        return $this->redirectToRoute('login');
    }


}
