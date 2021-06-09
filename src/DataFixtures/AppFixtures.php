<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{

    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    public function __construct( UserPasswordEncoderInterface $encoder )

    {
        $this->encoder = $encoder;
    }

    /**
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        $this->loadEtat($manager);
        $this->loadCampus($manager);
        $this->loadVille($manager);
        $this->loadLieu($manager);
        $this->loadParticipant($manager);
        $this->loadSortie($manager);

        $manager->flush();
    }

    public function loadEtat(ObjectManager $manager)
    {
        $etat_name = ['Créée', 'Ouverte', 'Clôturée', 'Activité en cours', 'Activité passée', 'Annulée', 'Archivée'];
        foreach ($etat_name as $kn => $name) {
            $etat = new Etat();
            $etat->setLibelle($name);
            $this->setReference('etat-' .$kn, $etat);
            $manager->persist($etat);
        }
        $manager->flush();
    }

    public function loadCampus(ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');

        for( $i =1; $i<11; $i++ ) {
            $campus = new Campus();
            $campus
                ->setNom($faker->departmentName);
            $this->setReference('campus-' .$i, $campus);
            $manager->persist($campus);
        }
        $manager->flush();
    }

    public function loadVille(ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');

        for( $i =1; $i<11; $i++ ) {
            $ville= new Ville();
            $ville
                ->setNom($faker->city)
                ->setCodePostal(rand(10,99) . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT));
            $this->setReference('ville-' .$i, $ville);
            $manager->persist($ville);
        }
        $manager->flush();
    }

    public function loadLieu(ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');

        for( $i =1; $i<11; $i++ ) {

            $lieu= new lieu();
            $lieu
                ->setNom($faker->city)
                ->setRue($faker->streetAddress)
                ->setVille($this->getReference('ville-'. rand(1,10)))
                ->setLatitude($faker->latitude(48,49))
                ->setLongitude($faker->longitude(-1.2,-1.7));
            $this->setReference('lieu-' .$i, $lieu);
            $manager->persist($lieu);
        }
        $manager->flush();
    }

    public function loadParticipant(ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');

        //admin
        $part = new Participant();
        $part
            ->setNom('Marx')
            ->setPrenom('Karl')
            ->setPseudo('Admin01')
            ->setTelephone('06' . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT))
            ->setEmail('admin@admin.admin')
            ->setPassword($this->encoder->encodePassword($part, '123'))
            ->setAdministrateur(1)
            ->setActif(1)
            ->setCampus($this->getReference('campus-'. rand(1,10)));
        $this->setReference('part-'. 1, $part);

        //gestion creation nom image de profil
        $image = '1.png';
        $part->setImageProfil($image);

        $manager->persist($part);
        $manager->flush();

        //user
        $part = new Participant();
        $part
            ->setNom('Wayne')
            ->setPrenom('Bruce')
            ->setPseudo('Batman')
            ->setTelephone('06' . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT))
            ->setEmail('user@user.user')
            ->setPassword($this->encoder->encodePassword($part, '123'))
            ->setAdministrateur(0)
            ->setActif(1)
            ->setCampus($this->getReference('campus-'. rand(1,10)));
        $this->setReference('part-'. 2, $part);

        //gestion creation nom image de profil
        $image = 'batman.png';
        $part->setImageProfil($image);

        $manager->persist($part);
        $manager->flush();


        for ($i = 3; $i<14; $i++) {
            $part = new Participant();

            //pour faire de jolis exemple avec le mail qui correspond aux noms et prenoms des participants
            $nom = $faker->lastName;
            $nomReduit = str_replace(' ', '', $nom);
            $prenom = $faker->firstName;
            $prenomReduit = str_replace(' ', '', $prenom);
            $email = $faker->email;
            $fin_email = substr($email, strpos($email, '@'));
            $email = $prenomReduit . '.' . $nomReduit . $fin_email;
            $pseudo = $prenomReduit . substr($nomReduit, 0, 3) . random_int(1, 99);

            $part
                ->setNom($nom)
                ->setPrenom($prenom)
                ->setPseudo($pseudo)
                ->setTelephone('06' . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT))
                ->setEmail($email)
                ->setPassword($this->encoder->encodePassword($part, '123'))
                ->setAdministrateur(0)
                ->setActif(1)
                ->setCampus($this->getReference('campus-' . rand(1, 10)));
            $this->setReference('part-' . $i, $part);

            $image = $i . '.png';
            $part->setImageProfil($image);

            $manager->persist($part);
            $manager->flush();
        }
    }

    /**
     * @throws \Exception
     */
    public function loadSortie(ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');

        for ($i = 1; $i<22; $i++) {
            $startDate = null;
            if ($i <= 3){
                $startDate = $faker->dateTimeBetween($startDate = 'now', $endDate = '+ 60 days', $timezone = 'Europe/Paris');
                //etat = créée = non publiée
                $etat = 0;

            }elseif ($i >= 4 && $i <= 9){
                $startDate = $faker->dateTimeBetween($startDate = 'now', $endDate = '+ 60 days', $timezone = 'Europe/Paris');
                //etat = ouverte = crée + publiée
                $etat = 1;

            } elseif ($i >= 10 && $i <= 12){
                $startDate = $faker->dateTimeBetween($startDate = 'now', $endDate = 'now', $timezone = 'Europe/Paris');
                //etat = en cours
                $etat = 3;

            } elseif ($i >= 13 && $i <= 15) {
                $startDate = $faker->dateTimeBetween($startDate = 'now', $endDate = '+ 60 days', $timezone = 'Europe/Paris');
                //etat = cloturée
                $etat = 2;

            } elseif ($i >= 16 && $i <= 18 ) {
                $startDate = $faker->dateTimeBetween($startDate = '- 29 days', $endDate = '- 1 days', $timezone = 'Europe/Paris');
                //etat = terminée
                $etat = 4;

            } elseif ($i >= 18 && $i <= 21 ) {
                $startDate = $faker->dateTimeBetween($startDate = 'now', $endDate = '+ 60 days', $timezone = 'Europe/Paris');
                //etat = annulée
                $etat = 5;

            } elseif ($i >= 22 && $i <= 25) {

                $startDate = $faker->dateTimeBetween($startDate = '- 75 days', $endDate = '- 30 days', $timezone = 'Europe/Paris');
                //etat = archivée
                $etat = 6;

            }

            $limitDate = date_format($startDate, 'Y-m-d');
            $limitDate = new \DateTime($limitDate);
            $limitDate->sub(date_interval_create_from_date_string('15 days'));

            $sortie = new Sortie();
            $sortie
                ->setNom($faker->text(45))
                ->setDateHeureDebut($startDate)
                ->setDuree(rand(5, 360))
                ->setDateLimiteInscription($limitDate)
                ->setNbInscriptionsMax(rand(2,25))
                ->setInfosSortie($faker->text(100))
                ->setEtat($this->getReference('etat-'.$etat))
                ->setCampus($this->getReference('campus-'. rand(1,10)))
                ->setOrganisateur($this->getReference('part-'. rand(1,12)))
                ->setLieu($this->getReference('lieu-'.rand(1,10)));

            //on va remplir la liste de participants
            if($sortie->getEtat()->getId() != 1){

                $part = $this->getReference('part-'. rand(1,12));
                if(!$sortie->getParticipants()->contains($part) && $part->getId() != $sortie->getOrganisateur()->getId()) {
                    $sortie->addParticipant($part);
                }

                $part = $this->getReference('part-'. rand(1,12));
                if(!$sortie->getParticipants()->contains($part) && $part->getId() != $sortie->getOrganisateur()->getId()) {
                    $sortie->addParticipant($part);
                }

                $part = $this->getReference('part-'. rand(1,12));
                if(!$sortie->getParticipants()->contains($part)
                    && $part->getId() != $sortie->getOrganisateur()->getId()
                    && $sortie->getParticipants()->count() < $sortie->getNbInscriptionsMax()) {
                    $sortie->addParticipant($part);
                }

                $part = $this->getReference('part-'. rand(1,12));
                if(!$sortie->getParticipants()->contains($part)
                    && $part->getId() != $sortie->getOrganisateur()->getId()
                    && $sortie->getParticipants()->count() < $sortie->getNbInscriptionsMax()) {
                    $sortie->addParticipant($part);
                }

                $part = $this->getReference('part-'. rand(1,12));
                if(!$sortie->getParticipants()->contains($part)
                    && $part->getId() != $sortie->getOrganisateur()->getId()
                    && $sortie->getParticipants()->count() < $sortie->getNbInscriptionsMax()) {
                    $sortie->addParticipant($part);
                }

                $part = $this->getReference('part-'. rand(1,12));
                if(!$sortie->getParticipants()->contains($part)
                    && $part->getId() != $sortie->getOrganisateur()->getId()
                    && $sortie->getParticipants()->count() < $sortie->getNbInscriptionsMax()) {
                    $sortie->addParticipant($part);
                }
            }

            if($sortie->getEtat()->getId() == 2 && $sortie->getParticipants()->count() == $sortie->getNbInscriptionsMax()){
                $sortie->setEtat($this->getReference('etat-2'));
            }

            $manager->persist($sortie);
        }
        $manager->flush();
    }

}
