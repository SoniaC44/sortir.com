<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ParticipantType;
use App\Repository\ParticipantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @Route("/participant", name="participant_")
 */
class ParticipantController extends AbstractController
{
    /**
     * @Route("/", name="index", methods={"GET"})
     */
    public function index(ParticipantRepository $participantRepository): Response
    {
        return $this->render('participant/index.html.twig', [
            'participants' => $participantRepository->findAll(),
        ]);
    }

     /**
     * @Route("/new", name="new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $participant = new Participant();
        $form = $this->createForm(ParticipantType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($participant);
            $entityManager->flush();

            return $this->redirectToRoute('participant_index');
        }

        return $this->render('participant/new.html.twig', [
            'participant' => $participant,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="show", methods={"GET"})
     */
    public function show(Participant $participant): Response
    {
        return $this->render('participant/show.html.twig', [
            'participant' => $participant,
        ]);
    }

    /**
     * @Route("/{id}/profil", name="profil", methods={"GET","POST"}, requirements={"id"="\d+"})
     */
    public function edit(Request $request, Participant $participant, UserPasswordEncoderInterface $encoder, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        if($user && $user->getId() == $participant->getId()){
            $form = $this->createForm(ParticipantType::class, $participant);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                $mdp = $form->get('motDePasse')->getData();

                if($mdp){
                    $hash = $encoder->encodePassword($participant, $mdp);
                    $participant->setPassword($hash);
                }

                $imageProfil = $form->get('imageProfil')->getData();

                if ($imageProfil) {

                    $newFilename = $participant->getId().'.'. $imageProfil->guessExtension();

                    // Move the file to the directory where brochures are stored
                    try {
                        $imageProfil->move(
                            $this->getParameter('image_profil_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        // ... handle exception if something happens during file upload
                    }

                    $participant->setImageProfil($newFilename);
                }

                $this->getDoctrine()->getManager()->flush();

                $message = "Votre profil a bien été mis à jour.";
                $this->addFlash("success", $message);

                return $this->redirectToRoute('main_home');
            }

            return $this->render('participant/edit.html.twig', [
                'participant' => $participant,
                'form' => $form->createView(),
            ]);
        }else{
            return $this->redirectToRoute('main_home');
        }
    }

    /**
     * @Route("/{id}", name="delete", methods={"POST"})
     */
    public function delete(Request $request, Participant $participant): Response
    {
        if ($this->isCsrfTokenValid('delete'.$participant->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($participant);
            $entityManager->flush();
        }

        return $this->redirectToRoute('participant_index');
    }

}
