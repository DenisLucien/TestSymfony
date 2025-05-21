<?php

namespace App\Controller\Admin;

use Symfony\Component\Routing\Requirement\Requirement;
// use App\Controller\Admin\Requirement;
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
use App\Entity\Category;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Demo;
use App\Repository\CategoryRepository;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

#[Route("/admin/recettes", name: "admin.recipe.")]
final class RecipeController extends AbstractController
{
    #[Route("/demo")]
    public function demo(Demo $demo)
    {
        dd($demo);
    }

    // #[IsGranted('ROLE_ADMIN') ]
    #[Route("/recette", name: "index")]
    public function index(
        Request $request,
        RecipeRepository $repository,
        EntityManagerInterface $em,
        CategoryRepository $categoryRepository
    ): Response {
        $page = $request->query->getInt("page", 1);
        $limit = 2;
        $recipes = $repository->paginateRecipes($request);
        $maxPage = ceil($recipes->count() / 2);

        return $this->render("admin/recipe/index.html.twig", [
            "recipes" => $recipes,
            "maxPage" => $maxPage,
            "page" => $page,
        ]);
    }

    #[
        Route(
            "/{id}",
            name: "edit",
            methods: ["GET", "POST"],
            requirements: ["id" => Requirement::DIGITS]
        )
    ]
    public function edit(
        Recipe $recipe,
        Request $request,
        EntityManagerInterface $em,
        FormFactoryInterface $formFactory,
        UploaderHelper $helper
    ): Response {
        $form = $formFactory->create(RecipeType::class, $recipe);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash("success", "La recette a bien été modifiée.");
            return $this->redirectToRoute("admin.recipe.index");
        }

        return $this->render("admin/recipe/edit.html.twig", [
            "recipe" => $recipe,
            "form" => $form,
        ]);
    }

    #[Route("/create", name: "create")]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        $recipe = new Recipe();
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $recipe->setCreatedAt(new \DateTimeImmutable());
            $recipe->setUpdatedAt(new \DateTimeImmutable());
            $em->persist($recipe);
            $em->flush();

            $this->addFlash("success", "La recette a bien été ajoutée.");

            return $this->redirectToRoute("admin.recipe.index");
        }

        return $this->render("admin/recipe/add.html.twig", [
            "form" => $form,
        ]);
    }

    #[
        Route(
            "/{id}",
            name: "delete",
            methods: ["DELETE"],
            requirements: ["id" => Requirement::DIGITS]
        )
    ]
    public function remove(
        Recipe $recipe,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $em->remove($recipe);
        $em->flush();
        $this->addFlash("success", "La recette a bien été supprimée.");
        return $this->redirectToRoute("admin.recipe.index");
    }
}
