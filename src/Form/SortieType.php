<?php

namespace App\Form;

use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Entity\Campus;
use App\Entity\Ville;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class SortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom')
            ->add('dateHeureDebut', DateTimeType::class, [
                'label' => 'Date et heure de la sortie',
                'widget' => 'single_text',
                'input_format' => 'Y-m-d H:i'
            ])
            ->add('dateLimiteInscription', DateType::class, [
                'label' => 'Date limite d\'inscription',
                'html5' => true,
                'widget' => 'single_text'
            ])
            ->add('nbInscriptionsMax')
            ->add('duree')


            ->add('infosSortie')

//            ->add('ville', EntityType::class, [
//                'class' => Ville::class,
//                'choice_label' => 'nom',
//                'placeholder' => 'Choisir une ville',
//            ])

            ->add('lieu', EntityType::class, [
                'class' => Lieu::class,
                'choice_label' => 'nom',
                'placeholder' => 'Choisir un lieu',
                'required' =>false
            ])
            ->add('nouveauLieu', LieuType::class, [
                'label' => 'Nouveau Lieu',
                'required' => false,
                'constraints' => [
                    new Valid()
                ]
            ])
        ;

        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit']);
    }

    function onSubmit(FormEvent  $event) {
        $sortie = $event->getData();
        $nouveauLieu = $event->getForm()->get('nouveauLieu')->getData();
        if (!$sortie->getLieu() && $nouveauLieu) {
            $sortie->setLieu($nouveauLieu);
        }

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }
}
