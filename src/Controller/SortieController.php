<?php

namespace App\Controller;

use App\Data\RechercheData;
use App\Entity\Etat;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\AnnulerSortieType;
use App\Form\RechercheSortieType;
use App\Form\SortieType;
use App\Repository\CampusRepository;
use App\Repository\EtatRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SortieRepository;
use DateInterval;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\User;

/**
 * @Route("/sortie")
 */
class SortieController extends AbstractController
{
    const ETAT_CLOTURE = "Clôturée";
    const ETAT_OUVERTE = "Ouverte";
    const ETAT_CREE = "Créée";
    const ETAT_PASSEE = "Activité passée";
    const ETAT_ANNULE = "Annulée";

    /**
     * @Route("/", name="sortie_index", methods={"GET","POST"})
     */
    public function index( SortieRepository $sortieRepository, EtatRepository $etatRepository, CampusRepository $campusRepository, ParticipantRepository $participantRepository, Request $request): Response
    {
        //on met à jour les activités du jour qui ont besoin de l'être
        $sorties = $sortieRepository->findAllEtatOuverteOuClotureeouEnCoursDuJour();
        $this->MettreAJourActivite($sorties, $sortieRepository, $etatRepository);

        //puis on affiche la liste des sortie
        $data = new RechercheData();

        $data->campus = $campusRepository->find($this->getUser()->getCampus());
        $form = $this->createForm(RechercheSortieType::class, $data);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data->user = $this->getUser();
        }

        return $this->render('sortie/index.html.twig', [
            'sorties' => $sortieRepository->findByFilters($data),
            'campus' => $campusRepository->findAll(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/new", name="sortie_new", methods={"GET","POST"})
     */
    public function new(Request $request, EtatRepository $etatRepository): Response
    {
        $sortie = new Sortie();
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);
        $sortie->setCampus($this->getUser()->getCampus());
        // Changer provisoire !!!
        $sortie->setEtat($etatRepository->findOneBy(['libelle' => 'Annulée']));

        $sortie->setOrganisateur($this->getUser());


        if ($form->isSubmitted()) {
            $entityManager = $this->getDoctrine()->getManager();

            $entityManager->persist($sortie);
            $entityManager->flush();

            return $this->redirectToRoute('sortie_index');
        }

        return $this->render('sortie/new.html.twig', [
            'sortie' => $sortie,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/maj", name="sortie_maj", methods={"GET"})
     */
    public function mettreAJourSorties(SortieRepository $sortieRepository, EtatRepository $etatRepository): Response {

        //on met à jour les activités passées qui ont besoin de l'être
        $sorties = $sortieRepository->findAllEtatOuverteOuClotureeOuEnCours();
        $this->MettreAJourActivite($sorties, $sortieRepository, $etatRepository);

        $this->cloturerLesSorties($sortieRepository, $etatRepository);
        $this->archiverLesSorties($sortieRepository, $etatRepository);

        return $this->redirectToRoute('sortie_index');
    }

    /**
     * @Route("/{id}/cancel", name="sortie_cancel", methods={"GET","POST"})
     */
    public function annulerSortie(Request $request, Sortie $sortie, EtatRepository $etatRepository)
    {
        //il faut être l'organisateur de la sortie pour pouvoir l'annuler
        if($sortie->getOrganisateur() == $this->getUser() || $this->getUser()->getAdministrateur())
        {
            //on ne peut annuler une sortie que si elle est à ouverte ou cloturée
            if($sortie->getEtat()->getLibelle() == self::ETAT_OUVERTE || $sortie->getEtat()->getLibelle() == self::ETAT_CLOTURE){
                $form = $this->createForm(AnnulerSortieType::class, $sortie);
                $form->handleRequest($request);

                if ($form->isSubmitted()) {
                    //on vérifie le bouton qui soumet le form
                    $btName = $form->getClickedButton()->getName();

                    //si bouton annuler redirectToRoute
                    if($btName == "reset"){
                        return $this->redirectToRoute('sortie_index');
                    }else{
                        //sinon on vérifie le form
                        if( $form->isValid()){

                            //modif sortie
                            $motif = $form->get('motif')->getData();

                            if($motif){

                                $sortie->setEtat($etatRepository->findOneBy(['libelle' => 'Annulée']));
                                $sortie->setInfosSortie($motif);

                                $this->getDoctrine()->getManager()->flush();

                                $message = "La sortie a bien été annulée.";
                                $this->addFlash("success", $message);

                            }
                            return $this->redirectToRoute('sortie_index');
                        }
                    }
                }

                return $this->render('sortie/cancel.html.twig', [
                    'sortie' => $sortie,
                    'form' => $form->createView(),
                ]);

            }else{
                $message = "L'état de la sortie '". $sortie->getNom() ."' ne lui permet plus d'être modifiée !";
                $this->addFlash("danger", $message);
                return $this->redirectToRoute('sortie_index');
            }

        }else{
            $message = "Vous ne pouvez pas annuler une sortie dont vous n'êtes pas l'organisateur !";
            $this->addFlash("danger", $message);
            return $this->redirectToRoute('sortie_index');
        }

    }

    /**
     * @Route("/{id}/edit", name="sortie_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Sortie $sortie): Response
    {
        //il faut être l'organisateur de la sortie pour pouvoir la modifier
        if($sortie->getOrganisateur() == $this->getUser())
        {
            //la sortie doit avoir un certain etat pour être modifiable :
            if($sortie->getEtat()->getLibelle() == self::ETAT_CREE)
            {
                $form = $this->createForm(SortieType::class, $sortie);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $this->getDoctrine()->getManager()->flush();

                    $message = "La sortie : '" . $sortie->getNom() . "' a bien été modifiée !";
                    $this->addFlash("success", $message);

                    return $this->redirectToRoute('sortie_index');
                }

                return $this->render('sortie/edit.html.twig', [
                    'sortie' => $sortie,
                    'form' => $form->createView(),
                ]);

            }else{
                $message = "Cette sortie n'est plus modifiable !";
                $this->addFlash("danger", $message);
                return $this->redirectToRoute('sortie_index');
            }

        }else{
            $message = "Vous ne pouvez pas modifier une sortie dont vous n'êtes pas l'organisateur !";
            $this->addFlash("danger", $message);
            return $this->redirectToRoute('sortie_index');
        }
    }

    /**
     * @Route("/{id}/delete", name="sortie_delete", methods={"GET"})
     */
    public function delete(Request $request, Sortie $sortie): Response
    {
        //il faut être l'organisateur de la sortie pour pouvoir la supprimer
       if ($sortie->getOrganisateur() == $this->getUser()) {

           //la sortie doit avoir un certain etat pour être modifiable :
           if($sortie->getEtat()->getLibelle() == self::ETAT_CREE)
           {
               $nomSortie = $sortie->getNom();

               $entityManager = $this->getDoctrine()->getManager();
               $entityManager->remove($sortie);
               $entityManager->flush();

               $message = "La sortie : '" . $nomSortie . "' a bien été supprimée !";
               $this->addFlash("success", $message);

           }else{

               $message = "La sortie : '" . $sortie->getNom() . "' n'est pas dans un état qui permet de la supprimer !";
               $this->addFlash("danger", $message);
           }

       }else{
           $message = "Vous ne pouvez pas supprimer une sortie dont vous n'êtes pas l'organisateur !";
           $this->addFlash("danger", $message);
       }

        return $this->redirectToRoute('sortie_index');
    }

    /**
     * @Route("/{id}/{action}", name="sortie_show", methods={"GET"})
     */
    public function show(Sortie $sortie, $action = 0): Response
    {

        if($action) {
            switch ($action){
                case 1:
                    $this->actionSeDesister($sortie);
                    break;
                case 2:
                    $this->actionSInscrire($sortie);
                    break;
                case 3:
                    $this->actionPublier($sortie);
                    break;
                default:
                    break;
            }

            return $this->redirectToRoute('sortie_index');
        }

        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
        ]);
    }

    // Récupère le nombre d'inscrits par sortie et vérifie que notre User est inscrit
    public function getInscriptions($sorties) {
        foreach ($sorties as $sortie) {
            $participants = $sortie->getParticipants();
            $sortie->nbInscrits = $participants->count();
            $sortie->isInscrit = false;

            foreach ($participants as $part) {
                if ($part == $this->getUser() ) {
                    $sortie->isInscrit = true;
                }
            }
        }
        return $sorties;
    }

    // Contrôle de la date limite de clôture des inscriptions + si déjà inscrit
    public function actionSeDesister($sortie) {

        //en premier lieu on vérifie l'etat de la sortie qui doit etre ouverte ou cloturée pour pouvoir se désinscrire
        if($sortie->getEtat()->getLibelle() == self::ETAT_OUVERTE || $sortie->getEtat()->getLibelle() == self::ETAT_CLOTURE){

            $user = $this->getUser();

            //on vérifie que l'user fait bien parti des participants
            if($sortie->getParticipants()->contains($user)){

                $sortie->removeParticipant($user);

                //si la date limite d'inscription n'est pas dépassée
                if ($sortie->getDateLimiteInscription() >= date_create('now')) {

                    //on regarde s'il reste de la place et si l'état etait à cloturée
                    if($sortie->getParticipants()->count() < $sortie->getNbInscriptionsMax()
                    && $sortie->getEtat()->getLibelle() === self::ETAT_CLOTURE){

                        $etatRepository = $this->getDoctrine()->getRepository(Etat::class);
                        $sortie->setEtat($etatRepository->findOneBy(['libelle' => 'Ouverte']));
                    }
                }

                $this->getDoctrine()->getManager()->flush();

                $message = "Vous êtes maintenant désinscrit de la sortie : " . $sortie->getNom();
                $this->addFlash("success", $message);

            }else{

                $message = "Vous n'êtes pas inscrit à la sortie : '" . $sortie->getNom() . "'. Vous ne pouvez donc pas vous désinscrire !";
                $this->addFlash("danger", $message);
            }

        }else{

            $message = "La sortie : '" . $sortie->getNom() . "' n'est pas dans un état qui permet de se désinscrire !";
            $this->addFlash("danger", $message);
        }
    }

    public function findByInscrit($data, ParticipantRepository $participantRepository) {
        $SortiesOfUser = null;

        if (!empty($data->user)) {
            $participant = $participantRepository->find($data->user);
            dd($participant);
        }
    }

    //methode qui ajoute un participant à la liste de participants d'une sortie si possible
    private function actionSInscrire(Sortie $sortie){

        //en premier lieu on vérifie l'etat de la sortie qui doit etre ouverte pour pouvoir s'inscrire
        if($sortie->getEtat()->getLibelle() == self::ETAT_OUVERTE){
            $user = $this->getUser();

            //on vérifie que l'user n'est pas déjà inscrit
            if(!$sortie->getParticipants()->contains($user))
            {
                if($sortie->getOrganisateur()->getId() == $user->getId())
                {
                    $message = "Vous êtes l'organisateur de cette sortie : " . $sortie->getNom();
                    $this->addFlash("warning", $message);
                }
                else{

                    //on vérifie le nombre d'inscrit
                    //normalement pas de lien vers l'inscription
                    if($sortie->getParticipants()->count() < $sortie->getNbInscriptionsMax()){

                        //on vérifie la date limite d'inscription
                        $dateJour = date_create('now');

                        if($dateJour <= $sortie->getDateLimiteInscription())
                        {
                            $sortie->addParticipant($user);

                            //si participants = nombre max on cloture la sortie
                            if($sortie->getParticipants()->count() == $sortie->getNbInscriptionsMax()){

                                $etatRepository = $this->getDoctrine()->getRepository(Etat::class);
                                $sortie->setEtat($etatRepository->findOneBy(['libelle' => 'Clôturée']));
                            }

                            $this->getDoctrine()->getManager()->flush();

                            $message = "Vous êtes maintenant inscrit à la sortie : " . $sortie->getNom();
                            $this->addFlash("success", $message);
                        }else{

                            $message = "La sortie : '" . $sortie->getNom() . "' est déjà clôturée. Vous ne pouvez pas vous y inscrire !";
                            $this->addFlash("danger", $message);
                        }

                    }else{

                        $message = "La sortie : '" . $sortie->getNom() . "' a déjà atteint son nombre de participants maximum.";
                        $this->addFlash("danger", $message);
                    }
                }
            }
            else{

                $message = "Vous êtes déjà inscrit à cette sortie : " . $sortie->getNom();
                $this->addFlash("danger", $message);
            }
        }else{
            $message = "L'état de la sortie : '" . $sortie->getNom() . "' ne lui permet plus d'ajouter des participants.";
            $this->addFlash("danger", $message);
        }
        return $this->redirectToRoute('sortie_index');
    }

    //methode qui met l'etat d'une sortie à "ouverte" si possible
    private function actionPublier(Sortie $sortie){

        if($sortie->getOrganisateur() == $this->getUser()){

            if($sortie->getEtat()->getLibelle() == self::ETAT_CREE){

                $dateJour = date_create('now');

                if ( $sortie->getDateHeureDebut() > $dateJour
                    && $sortie->getDateLimiteInscription() > $dateJour) {

                    $etatRepository = $this->getDoctrine()->getRepository(Etat::class);
                    $sortie->setEtat($etatRepository->findOneBy(['libelle' => 'Ouverte']));

                    $this->getDoctrine()->getManager()->flush();

                    $message = "La sortie : '" . $sortie->getNom() . "' a bien été publiée.";
                    $this->addFlash("success", $message);

                }else{

                    $message = "La sortie : '" . $sortie->getNom() . "' n'a pas pu être publiée. La date de sortie et/ou la date de clotûre est/sont dépassée/s.";
                    $this->addFlash("danger", $message);
                }
            }else{
                $message = "La sortie : '" . $sortie->getNom() . "' n'est pas dans un état qui permette sa publication !";
                $this->addFlash("danger", $message);

            }

        }else{

            $message = "Vous ne pouvez pas modifier une sortie dont vous n'êtes pas l'organisateur !";
            $this->addFlash("danger", $message);
        }

        return $this->redirectToRoute('sortie_index');
    }

    //methode qui modifie l'etat d'une sortie pour l'archiver selon certaines conditions
    private function archiverLesSorties(SortieRepository $sortieRepository, EtatRepository $etatRepository){

        $sorties = $sortieRepository->findAllAArchiver();

        foreach($sorties as $sortie) {
            $sortie->setEtat($etatRepository->findOneBy(['libelle' => 'Archivée']));
        }

        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('sortie_index');
    }

    //methode qui modifie l'etat d'une sortie pour la cloturer selon certaines conditions
    private function cloturerLesSorties(SortieRepository $sortieRepository, EtatRepository $etatRepository){

        $sorties = $sortieRepository->findAll();

        $today = date_create('now');

        foreach($sorties as $sortie)
        {
            //on cloture les sorties ouvertes
            if($sortie->getEtat()->getLibelle() == self::ETAT_OUVERTE){

                //on vérifie pour chaque sortie le nombre d'inscrits
                //normalement vérification faite lors de l'inscription
                if($sortie->getParticipants()->count() == $sortie->getNbInscriptionsMax())
                {
                    $sortie->setEtat($etatRepository->findOneBy(['libelle' => 'Clôturée']));
                }

                //si le nombre d'inscrits est inférieur au nombre max on vérifie la date limite
                if($sortie->getDateLimiteInscription() < $today )
                {
                    $sortie->setEtat($etatRepository->findOneBy(['libelle' => 'Clôturée']));
                }
            }
        }

        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('sortie_index');
    }

    //methode qui met à jour l'etat des activités en cours, cloturée ou ouverte
    private function MettreAJourActivite($sorties, SortieRepository $sortieRepository, EtatRepository $etatRepository)
    {
        foreach ($sorties as $sortie) {

            $maintenant = new DateTime('now');

            $debutSortie = clone $sortie->getDateHeureDebut();

            $intervalString = "PT" . $sortie->getDuree() . "M";
            $duree = new DateInterval($intervalString);

            $heureFinSortie = date_add($debutSortie, $duree);

            if ($sortie->getDateHeureDebut() < $maintenant && $heureFinSortie > $maintenant) {

                //sortie en cours
                $sortie->setEtat($etatRepository->findOneBy(['libelle' => 'Activité en cours']));

            }else if($heureFinSortie <= $maintenant){

                //sortie passée
                $sortie->setEtat($etatRepository->findOneBy(['libelle' => 'Activité passée']));
            }
        }

        $this->getDoctrine()->getManager()->flush();
    }


}
