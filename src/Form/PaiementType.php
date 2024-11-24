<?php

namespace App\Form;

use App\Entity\Paiement;
use App\Entity\Caisse;
use App\Entity\Beneficiaire;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaiementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('caisse', EntityType::class, [
                'class' => Caisse::class,
                'choice_label' => 'nom',
                'label' => 'Caisse',
                'required' => true,
            ])
            ->add('beneficiaire', EntityType::class, [
                'class' => Beneficiaire::class,
                'choice_label' => 'nom',
                'label' => 'Bénéficiaire',
                'required' => true,
            ])
            ->add('montant', NumberType::class, [
                'label' => 'Montant',
                'required' => true,
            ])
            ->add('methode', ChoiceType::class, [
                'label' => 'Méthode de paiement',
                'choices' => [
                    'MTN Money' => 'mtn',
                    'Orange Money' => 'orange',
                ],
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Paiement::class,
        ]);
    }
}