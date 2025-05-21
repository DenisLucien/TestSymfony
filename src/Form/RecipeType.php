<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use App\Entity\Recipe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class RecipeType extends AbstractType
{
    public function __construct(private FormListenerFactory $listenerFactory) {}

    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add("title", TextType::class, ["empty_data" => ""])
            ->add("slug", null, ["required" => false])
            ->add("thumbnailFile", FileType::class, [  
            ])
            ->add("category", EntityType::class, [
                "class" => Category::class,
                "expanded" => true,
                "choice_label" => "name",
            ])
            ->add("content", TextareaType::class, ["empty_data" => ""])
            ->add("createdAt", null, [
                "widget" => "single_text",
                "required" => false,
            ])
            ->add("updatedAt", null, [
                "widget" => "single_text",
                "required" => false,
            ])
            ->add("duration")
            ->add("save", SubmitType::class, ["label" => "Envoyer"])
            ->addEventListener(
                FormEvents::PRE_SUBMIT,
                $this->listenerFactory->autoSlug("title"),
            )
            ->addEventListener(
                FormEvents::POST_SUBMIT,
                $this->listenerFactory->timeStamps(),
            );
    }

    public function autoSlug(FormEvent $event): void
    {
        $data = $event->getData();
        if (empty($data["slug"])) {
            $slugger = new AsciiSlugger();
            $data["slug"] = strtolower($slugger->slug($data["title"]));
            $event->setData($data);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "data_class" => Recipe::class,
        ]);
    }
    public function attachTimestamps(PostSubmitEvent $event): void
    {
        $data = $event->getData();
        if (!($data instanceof Recipe)) {
            return;
        }
        $data->setUpdatedAt(new \DateTimeImmutable());
        if (!$data->getId()) {
            $data->setCreatedAt(new \DateTimeImmutable());
        }
    }
}
