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
        $etat_name = ['Créée', 'Ouverte', 'Clôturée', 'Activité en cours', 'Passée', 'Annulée'];
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
                ->setLatitude($faker->latitude)
                ->setLongitude($faker->longitude);
            $this->setReference('lieu-' .$i, $lieu);
            $manager->persist($lieu);
        }
        $manager->flush();
    }

    public function loadParticipant(ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');

        $part = new Participant();
        $part
            ->setNom('Marx')
            ->setPrenom('Karl')
            ->setPseudo('KaMar007')
            ->setTelephone('06' . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT))
            ->setEmail('admin@admin.admin')
            ->setPassword($this->encoder->encodePassword($part, '123'))
            ->setAdministrateur(1)
            ->setActif(1)
            ->setCampus($this->getReference('campus-'. rand(1,10)));
        $this->setReference('part-'. 1, $part);
        $manager->persist($part);


        for ($i = 2; $i<24; $i++) {
            $part = new Participant();

            //pour faire de jolis exemple avec le mail qui correspond aux noms et prenoms des participants
            $nom = $faker->lastName;
            $nomReduit = str_replace(' ','',$nom);
            $prenom = $faker->firstName;
            $prenomReduit = str_replace(' ','',$prenom);
            $email = $faker->email;
            $fin_email = substr($email, strpos($email,'@'));
            $email = $prenomReduit.'.'.$nomReduit.$fin_email;
            $pseudo =  $prenomReduit . substr($nomReduit, 0,3) . random_int(1,99);

            $part
                ->setNom($nom)
                ->setPrenom($prenom)
                ->setPseudo($pseudo)
                ->setTelephone('06' . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT))
                ->setEmail($email)
                ->setPassword($this->encoder->encodePassword($part, '123'))
                ->setAdministrateur(0)
                ->setActif(1)
                ->setCampus($this->getReference('campus-'. rand(1,10)));
            $this->setReference('part-'.$i, $part);
            $manager->persist($part);
        }
        $manager->flush();
    }

    /**
     * @throws \Exception
     */
    public function loadSortie(ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');

        for ($i = 1; $i<26; $i++) {
            $startDate = null;
            $etat = 1;
            if ($i <= 10){
                $startDate = $faker->dateTimeBetween($startDate = 'now', $endDate = '+ 60 days', $timezone = 'Europe/Paris');

            } elseif ($i >= 11 && $i <= 16){
                $startDate = $faker->dateTimeBetween($startDate = 'now', $endDate = 'now', $timezone = 'Europe/Paris');
                $etat = 3;

            } elseif ($i >= 17 && $i <= 25 ) {
                $startDate = $faker->dateTimeBetween($startDate = '- 29 days', $endDate = '- 1 days', $timezone = 'Europe/Paris');
                $etat = 4;

            }
            $limitDate = date_format($startDate, 'Y-m-d');

            $sortie = new Sortie();
            $sortie
                ->setNom($faker->text(45))
                ->setDateHeureDebut($startDate)
                ->setDuree(rand(5, 360))
                ->setDateLimiteInscription(new \DateTime($limitDate))
                ->setNbInscriptionsMax(rand(2,25))
                ->setInfosSortie($faker->text(100))
                ->setEtat($this->getReference('etat-'.$etat))
                ->setCampus($this->getReference('campus-'. rand(1,10)))
                ->setOrganisateur($this->getReference('part-'. rand(1,23)))
                ->setLieu($this->getReference('lieu-'.rand(1,10)));
            $manager->persist($sortie);
        }
        $manager->flush();
    }

}
