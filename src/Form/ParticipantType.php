<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Participant;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints as Assert;

class ParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('pseudo', TextType::class)
            ->add('prenom', TextType::class)
            ->add('nom', TextType::class)
            ->add('telephone', TextType::class, ['required' => false])
            ->add('email')
            ->add('motDePasse', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Le mot de passe et sa confirmation doivent être identiques.',
                'mapped' => false,
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => false,
                'first_options'  => ['label' => 'Mot de passe',
                    'constraints'=>[
                        new Assert\Length([
                            'min' => 3,
                            'max' => 20,
                            'minMessage' => 'Votre mot de passe est trop court (3 caractères au minimum)',
                            'maxMessage' => 'Votre mot de passe est trop long (20 caractères au maximum)',
                        ])
                    ]],
                'second_options' => ['label' => 'Confirmation']
            ])
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'nom',
                'attr' => ['class' => 'form-select']
            ])
            ->add('imageProfil', FileType::class, [
                'label' => 'Ma photo',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpeg',
                        ],
                        'mimeTypesMessage' => 'Merci de télécharger une image valide (jpg, png)',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
        ]);
    }
}
