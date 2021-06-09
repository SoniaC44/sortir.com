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
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
                'html5' => true,
                'widget' => 'single_text',
                'input_format' => 'Y-m-d H:i',
                'attr' => ['class' => 'form-control']

            ])
            ->add('dateLimiteInscription', DateType::class, [
                'label' => 'Date limite d\'inscription',
                'html5' => true,
                'widget' => 'single_text',
                'input_format' => 'd/m/Y',
                'attr' => ['class' => 'form-control']
            ])
            ->add('nbInscriptionsMax')
            ->add('duree')
            ->add('infosSortie')

            ->add('ville', EntityType::class, [
                'mapped' => false,
                'class' => Ville::class,
                'choice_label' => 'nom',
                'placeholder' => 'Choisir une ville',
                'attr' => [
                    'class' => 'form-select',
                ]
            ])
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'nom',
                'attr' => ['class' => 'form-select']

            ])
            ->add('rue', TextType::class, [
                'mapped' =>false,
                'disabled' => true])
            ->add('codePostal', TextType::class, [
                'label'=> "Code postal",
                'mapped' => false,
                'disabled' => true])
            ->add('latitude', TextType::class, [
                'mapped' =>false,
                'disabled' => true])
            ->add('longitude', TextType::class, [
                'mapped' =>false,
                'disabled' => true])

            ->add('nouveauLieu', LieuType::class, [
                'label' => 'Nouveau Lieu',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Valid()
                ]
            ])
        ;

        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit']);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'));
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
    }


    function onSubmit(FormEvent  $event) {
        $sortie = $event->getData();
        $nouveauLieu = $event->getForm()->get('nouveauLieu')->getData();
        if (!$sortie->getLieu() && $nouveauLieu) {
            $nouveauLieu->setVille($event->getForm()->get('ville')->getData());
            $sortie->setLieu($nouveauLieu);
        }
    }

    function onPostSetData(FormEvent $event){
        $sortie = $event->getData();
        if($sortie && $sortie->getId() !== null){
            $form = $event->getForm();

            $lieu = $sortie->getLieu();
            $form->get('rue')->setData($lieu->getRue());
            $form->get('latitude')->setData($lieu->getLatitude());
            $form->get('longitude')->setData($lieu->getLongitude());
            $form->get('codePostal')->setData($lieu->getVille()->getCodePostal());
            $form->get('ville')->setData($lieu->getVille());

        }
    }

    function onPreSetData(FormEvent $event){
        $sortie = $event->getData();
        $form = $event->getForm();

        if ($sortie && $sortie->getId() !== null ) {

            $lieu = $sortie->getLieu();
            $ville = $lieu->getVille();

            $lieux = $ville->getLieux();

            $form->add('lieu', EntityType::class, [
                'class' => Lieu::class,
                'choices' => $lieux,
                'choice_label' => 'nom',
                'choice_attr' => function($choice) {
                    return [
                        'data-rue' => $choice->getRue(),
                        'data-lat' => $choice->getLatitude(),
                        'data-long' => $choice->getLongitude(),
                        'data-codep' => $choice->getVille()->getCodePostal()
                    ];
                },
                'placeholder' => false,
                'required' => false,
                'attr' => ['class' => 'form-select', 'data-rue' => 'test']
            ]);

            $form->get('lieu')->getConfig()->getOption('choices');

        }else{

            $form->add('lieu', EntityType::class, [
                'class' => Lieu::class,
                'choices' => [],
                'choice_label' => 'nom',
                'placeholder' => false,
                'required' => false,
                'attr' => ['class' => 'form-select']
            ]);

        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }
}
