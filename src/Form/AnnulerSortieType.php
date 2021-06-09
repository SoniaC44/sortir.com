<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Lieu;
use App\Entity\Sortie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AnnulerSortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('motif', TextAreaType::class, [
                'label' => 'Motif',
                'mapped' => false,
                'required' => false,
                'constraints'=>[
                    new Assert\NotBlank([
                        'message' => "Le motif d'annulation est invalide",
                    ])
                ]]
            )
            ->add('save', SubmitType::class, ['label' => 'Enregistrer','attr' => ['class' => 'btn btn-outline-dark']])
            ->add('reset', SubmitType::class, ['label' => 'Annuler','attr' => ['class' => 'btn btn-outline-dark']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }
}
