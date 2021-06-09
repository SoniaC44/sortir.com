<?php

namespace App\Controller\API;

use App\Entity\Lieu;
use App\Repository\LieuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ApiLieuController extends AbstractController
{

    /**
     * @Route("lieuxParVille/{id_ville}", name="liste_apiLieu")
     */
    public function liste(LieuRepository $lieuRepository, $id_ville, SerializerInterface $serializer){

        $lieux = $lieuRepository->findAllByVille($id_ville);
        $tabLieux = [];

        foreach($lieux as $lieu){
            $tabLieux[] = $this->serialiseurLieu($lieu);
        }

        $json = $serializer->serialize($tabLieux, 'json');

        return new JsonResponse($json, Response::HTTP_OK, [], true);

    }

    private function serialiseurLieu(Lieu $lieu): array
    {

        return [
            'id'   => $lieu->getId(),
            'nom' => $lieu->getNom(),
            'rue' => $lieu->getRue(),
            'latitude' => $lieu->getLatitude(),
            'longitude' => $lieu->getLongitude(),
            'codePostal' => $lieu->getVille()->getCodePostal(),
        ];

    }

    /**
     * @Route("lieuInfos/{id_lieu}", name="infos_apiLieu")
     */
    public function infosParLieu(LieuRepository $lieuRepository, $id_lieu, SerializerInterface $serializer){

        $lieu = $lieuRepository->find($id_lieu);

        $json = $serializer->serialize($lieu, 'json', ['groups' => 'liste_Lieu']);

        return new JsonResponse($json, Response::HTTP_OK, [], true);

    }


}