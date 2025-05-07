<?php

namespace App\Form;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use App\Entity\Recipe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class RecipeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('slug',null,['required'=>false,])
            ->add('content')
            ->add('createdAt', null, [
                'widget' => 'single_text','required'=>false
            ])
            ->add('updatedAt', null, [
                'widget' => 'single_text','required'=>false
            ])
            ->add('duration')
            ->add('save',SubmitType::class, ['label'=>'Envoyer'])
            ->addEventListener(FormEvents::PRE_SUBMIT,$this-> autoSlug(...))
            ->addEventListener(FormEvents::POST_SUBMIT,$this-> attachTimestamps(...))
        ;
    }

    public function autoSlug(FormEvent $event): void{
        $data=$event->getData();   
            if(empty($data['slug']))
            {
            $slugger=  new AsciiSlugger();
            $data['slug']=strtolower($slugger->slug($data['title']));
            $event->setData($data);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recipe::class,
            'validation_groups'=>['Default']
        ]);
    }
    public function attachTimestamps(PostSubmitEvent $event): void
    {
        $data=$event->getData();
        if (!($data instanceof Recipe)){
            return;
        }
        $data->setUpdatedAt(new \DateTimeImmutable());
        if(!$data->getId()){
            $data->setCreatedAt(new \DateTimeImmutable());
        }

    }
}
