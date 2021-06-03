<?php

namespace App\Controller;

use App\Data\RechercheData;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\RechercheSortieType;
use App\Form\SortieType;
use App\Repository\CampusRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SortieRepository;
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
    public function index(SortieRepository $sortieRepository, CampusRepository $campusRepository, ParticipantRepository $participantRepository, Request $request): Response
    {

        $data = new RechercheData();

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
     * @Route("/{id}", name="sortie_show", methods={"GET"})
     */
    public function show(Sortie $sortie,int $action = 0): Response
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
        if ($this->isCsrfTokenValid('delete'.$sortie->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($sortie);
            $entityManager->flush();
        }

        return $this->redirectToRoute('sortie_index');
    }

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

    public function findByInscrit($data, ParticipantRepository $participantRepository) {
        $SortiesOfUser = null;

        if (!empty($data->user)) {
            $participant = $participantRepository->find($data->user);
            dd($participant);
        }
    }

    public function actionSInscrire(Sortie $sortie){

        if($sortie->getEtat() == 2){
            $user = $this->getUser();
            $sortie->addParticipant($user);

            $message = "vous êtes inscrit à la sortie : " . $sortie->getNom();
            $this->addFlash("success", $message);

            return $this->redirectToRoute('sortie_index');
        }
    }

    private function actionAnnuler(Sortie $sortie)
    {
    }

    private function actionSeDesister(Sortie $sortie)
    {
    }
}
