<?php


namespace App\Form;

use App\Data\RechercheData;
use App\Entity\Campus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RechercheSortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
           ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'nom',
                'label' => 'Campus :',
                'required' => false
            ])
            ->add('dateMin', DateType::class, [
                'label' => 'Entre',
                'required' => false,
                'html5' => true,
                'widget' => 'single_text'
            ])

            ->add('dateMax', DateType::class, [
                'label' => 'et',
                'required' => false,
                'html5' => true,
                'widget' => 'single_text'
            ])
            ->add('mot', SearchType::class, [
                'label' => 'Le nom de la sortie contient:',
                'required' => false,
                'attr' => [
                    'placeholder' => '🔍 search'
                ]

            ])
            ->add('passee', CheckboxType::class, [
                'label' => 'Sorties passées',
                'required' => false,
            ])
            ->add('inscrit', CheckboxType::class, [
                'label' => 'Sorties auxquelles je suis inscrit/e',
                'required' => false,
            ])
            ->add('nonInscrit', CheckboxType::class, [
                'label' => 'Sorties auxquelles je ne suis pas inscrit/e',
                'required' => false,
            ])
            ->add('organisee', CheckboxType::class, [
                'label' => 'Sorties dont je suis l\'organisateur/trice',
                'required' => false,
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RechercheData::class,
            'method' => 'GET',
            'crsf_protection' => false

        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

}