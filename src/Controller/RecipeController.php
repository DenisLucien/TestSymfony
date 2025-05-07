<?php

namespace App\Controller;

use App\Form\RecipeType;
use App\Repository\RecipeRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Recipe;




final class RecipeController extends AbstractController
{
    #[Route('/recette', name: 'recipe.index')]
    public function index(Request $request, RecipeRepository $repository, EntityManagerInterface  $em): Response
    {
        $recipes=$repository->findAll();
    //    dd($recipes);
        

        return $this->render('recipe/index.html.twig',[
            'recipes'=>$recipes
        ]);


    }

    #[Route('/recette/{slug}-{id}', name: 'recipe.show',requirements :['id'=>'\d+','slug'=>'[a-z0-9\s\-âêîôûäëïöüàèìòùáéíóúç]+'])]
    public function show(Request $request, string $slug,int $id , RecipeRepository $repository): Response
    {
        $recipe = $repository-> find($id);
        if( $recipe->getSlug() != $slug){
            return $this->redirectToRoute('recipe.show',['slug'=>$recipe->getSlug(),'i'=>$recipe->getId()]);
        }
        // dd($recipe);
        return $this->render('recipe/show.html.twig',[
            'slug' => $slug,
            'id' => $id,
            'recipe'=> $recipe
        ]);
}

#[Route('/recette/{id}/edit', name: 'recipe.edit',methods:['GET','POST'])]
public function edit(Recipe $recipe,Request $request, EntityManagerInterface  $em ): Response
{
    $form=$this->createForm(RecipeType::class, $recipe);
    $form->handleRequest($request);
    if($form->isSubmitted() && $form->isValid()){
        $em->flush();
        $this->addFlash('success','La recette a bien été modifiée.');
        return $this->redirectToRoute('recipe.index');
    }

    return $this->render('recipe/edit.html.twig',[
        'recipe'=> $recipe,
        'form'=> $form
        
    ]);
}

#[Route('/recette/add', name: 'recipe.add')]
public function add(Request $request, EntityManagerInterface  $em ): Response
{
    $recipe=new Recipe();
    $form=$this->createForm(RecipeType::class, $recipe);
    $form->handleRequest($request);
    if($form->isSubmitted() && $form->isValid()){
        $recipe->setCreatedAt(new \DateTimeImmutable());
        $recipe->setUpdatedAt(new \DateTimeImmutable());
        $em ->persist($recipe);
        $em->flush();

        $this->addFlash('success','La recette a bien été ajoutée.');

        return $this->redirectToRoute('recipe.index');
    }

    return $this->render('recipe/add.html.twig',[
        'form'=> $form
        
    ]);
}

#[Route('/recette/{id}/edit', name: 'recipe.delete',methods:['DELETE'])]
public function remove(Recipe $recipe,Request $request, EntityManagerInterface  $em ): Response
{
   $em->remove($recipe);
   $em->flush();
    $this->addFlash('success','La recette a bien été supprimée.');
    return $this-> redirectToRoute('recipe.index');
}

}