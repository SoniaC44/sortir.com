<?php

namespace App\Controller;

use App\Data\RechercheData;
use App\Entity\Etat;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\RechercheSortieType;
use App\Form\SortieType;
use App\Repository\CampusRepository;
use App\Repository\EtatRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SortieRepository;
use Cassandra\Date;
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
    /**
     * @Route("/", name="sortie_index", methods={"GET","POST"})
     */
    public function index( SortieRepository $sortieRepository, CampusRepository $campusRepository, ParticipantRepository $participantRepository, Request $request): Response
    {

        $data = new RechercheData();


        if ($data->campus == null){
            $data->campus = $campusRepository->find($this->getUser()->getCampus());
        }
        $form = $this->createForm(RechercheSortieType::class, $data);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data->user = $this->getUser();


            return $this->render('sortie/index.html.twig', [
                'sorties' => $this->getInscriptions($sortieRepository->findByFilters($data)),
                'campus' => $campusRepository->findAll(),
                'form' => $form->createView(),
            ]);
        }

        return $this->render('sortie/index.html.twig', [
            'sorties' => $this->getInscriptions($sortieRepository->findAll()),
            'campus' => $campusRepository->findAll(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/new", name="sortie_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $sortie = new Sortie();
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);
        $sortie->setCampus($this->getUser()->getCampus());

        if ($form->isSubmitted() && $form->isValid()) {
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
        $this->cloturerLesSorties($sortieRepository, $etatRepository);
        $this->archiverLesSorties($sortieRepository, $etatRepository);

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
                    $this->actionAnnuler($sortie);
                    break;
                case 4:
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

    /**
     * @Route("/{id}/edit", name="sortie_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Sortie $sortie): Response
    {
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('sortie_index');
        }

        return $this->render('sortie/edit.html.twig', [
            'sortie' => $sortie,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="sortie_delete", methods={"POST"})
     */
    public function delete(Request $request, Sortie $sortie): Response
    {
       if ($sortie->getOrganisateur() == $this->getUser()) {
           if ($this->isCsrfTokenValid('delete'.$sortie->getId(), $request->request->get('_token'))) {
               $entityManager = $this->getDoctrine()->getManager();
               $entityManager->remove($sortie);
               $entityManager->flush();
           }
       }

        return $this->redirectToRoute('sortie_index');
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

        $participants = $sortie->getParticipants();
        if ($sortie->getDateLimiteInscription() >= date_create('now')->format('Y-m-d H:i:s')) {
            foreach ($participants as $part) {
                if ($part == $this->getUser() ) {
                    $sortie->removeParticipant($this->getUser());
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($sortie);
                    $entityManager->flush();
                }
            }
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
        if($sortie->getEtat()->getId() == 2){
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

                        $sortie->addParticipant($user);

                        //si participants = nombre max on cloture la sortie
                        if($sortie->getParticipants()->count() == $sortie->getNbInscriptionsMax()){

                            $etatRepository = $this->getDoctrine()->getRepository(Etat::class);
                            $sortie->setEtat($etatRepository->find(3));
                        }

                        $this->getDoctrine()->getManager()->flush();

                        $message = "Vous êtes maintenant inscrit à la sortie : " . $sortie->getNom();
                        $this->addFlash("success", $message);

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
        }
        return $this->redirectToRoute('sortie_index');
    }

    //methode qui met l'etat d'une sortie à "ouverte" si possible
    private function actionPublier(Sortie $sortie){

        if($sortie->getOrganisateur() == $this->getUser() && $sortie->getEtat()->getId() == 1 ) {

            $dateJour = date_create('now');

            if ( $sortie->getDateHeureDebut() > $dateJour
                && $sortie->getDateLimiteInscription() > $dateJour) {

                $etatRepository = $this->getDoctrine()->getRepository(Etat::class);
                $sortie->setEtat($etatRepository->find(2));

                $this->getDoctrine()->getManager()->flush();

                $message = "La sortie : '" . $sortie->getNom() . "' a bien été publiée.";
                $this->addFlash("success", $message);
            }else{
                $message = "La sortie : '" . $sortie->getNom() . "' n'a pas pu être publiée. La date de sortie et/ou la date de clotûre est/sont dépassée/s.";
                $this->addFlash("danger", $message);
            }
            return $this->redirectToRoute('sortie_index');
        }
    }

    private function actionAnnuler(Sortie $sortie)
    {
    }

    //methode qui modifie l'etat d'une sortie pour l'archiver selon certaines conditions
    private function archiverLesSorties(SortieRepository $sortieRepository, EtatRepository $etatRepository){

        $sorties = $sortieRepository->findAll();

        $today = date_create('now');

        foreach($sorties as $sortie) {

            //on archive les sorties terminées ou annulées après un mois
            if ($sortie->getEtat()->getId() == 5 || $sortie->getEtat()->getId() == 6) {

                $dateSortie = date_create($sortie->getDateHeureDebut()->format('Y-m-d'));
                $datePlus1Month = date_add($dateSortie, date_interval_create_from_date_string('1 month'));

                if ($datePlus1Month < $today) {
                    $sortie->setEtat($etatRepository->find(7));
                }
            }
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
            if($sortie->getEtat()->getId() == 2){

                //on vérifie pour chaque sortie le nombre d'inscrits
                //normalement vérification faite lors de l'inscription
                if($sortie->getParticipants()->count() == $sortie->getNbInscriptionsMax())
                {
                    $sortie->setEtat($etatRepository->find(3));
                }

                //si le nombre d'inscrits est inférieur au nombre max on vérifie la date limite
                if($sortie->getDateLimiteInscription() < $today )
                {
                    $sortie->setEtat($etatRepository->find(3));
                }
            }
        }

        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('sortie_index');
    }

    private function MettreAJourActivite(SortieRepository $sortieRepository, EtatRepository $etatRepository)
    {

        //TODO à finir !! vérifier les comparaisons de date

        $sorties = $sortieRepository->findAll();

        $maintenant = new DateTime('now');
        $today = date_create((new DateTime('now'))->format('Y-m-d'));

        foreach ($sorties as $sortie) {

            //pour qu'on modifie l'activité à en cours ou terminée il faut vérifier l'etat
            //la sortie doit être à ouverte ou cloturée
            if ($sortie->getEtat()->getId() == 2 || $sortie->getEtat()->getId() == 3) {

                $jourSortie = date_create($sortie->getDateHeureDebut()->format('Y-m-d'));
                $heureSortie = date_create($sortie->getDateHeureDebut()->format('Y-m-d HH:mm'));

                //on compare la date du jour avec la date de sortie
                if ($jourSortie == $today && $heureSortie < $maintenant) {

                    //sortie en cours
                    $sortie->setEtat($etatRepository->find(4));
                }
            }

            //l'activité ne doit ni être annulée ni archivée ni non publiée
            //pour passer à activité terminée si la date de l'activité est passée
            if ($sortie->getEtat() != 6 && $sortie->getEtat() != 7 && $sortie->getEtat() != 1) {

                $jourSortie = date_create($sortie->getDateHeureDebut()->format('Y-m-d'));
                $debutSortie = date_create($sortie->getDateHeureDebut()->format('Y-m-d HH:mm'));

                $intervalString = "PT" . $sortie->getDuree() . "M";
                $duree = new DateInterval($intervalString);

                $heureFinSortie = date_add($debutSortie, $duree);

                if ($jourSortie < $today || ($jourSortie == $today && $heureFinSortie < $maintenant)) {

                    //sortie en cours
                    $sortie->setEtat($etatRepository->find(5));
                }

            }
        }
    }
}
