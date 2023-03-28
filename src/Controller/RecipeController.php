<?php

namespace App\Controller;

use App\Entity\Mark;
use App\Entity\Recipe;
use App\Form\MarkType;
use App\Form\RecipeType;
use App\Repository\MarkRepository;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class RecipeController extends AbstractController
{
    #[Route('/recette', name: 'recipe')]
    #[IsGranted('ROLE_USER')]
    public function index(RecipeRepository $repository, PaginatorInterface $paginator, Request $request): Response
    {
        $recipes = $paginator->paginate(
            $repository->findBy(['user' => $this->getUser()]),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('pages/recipe/index.html.twig', [
            'recipes' => $recipes
        ]);
    }

    #[Route('/recette/publique', name: 'recipe_public')]
    public function indexPublic(PaginatorInterface $paginator, RecipeRepository $repository, Request $request): Response
    {
        $recipes = $paginator->paginate(
            $repository->findPublicRecipe(null),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('pages/recipe/index_public.html.twig', [
            'recipes' => $recipes
        ]);
    }

    #[Route('/recette/{id}', name: 'recipe_show')]
    #[Security("is_granted('ROLE_USER') and recipe.getIsPublic() === true")]
    public function show(Recipe $recipe, Request $request, MarkRepository $repo, EntityManagerInterface $manager): Response
    {
        $mark = new Mark();

        $form = $this->createForm(MarkType::class, $mark);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $mark->setUser($this->getUser())
                 ->setRecipe($recipe);

            $existingMark = $repo->findOneBy([
                'user' => $this->getUser(),
                'recipe' => $recipe
            ]);

            if(!$existingMark){
                $manager->persist($mark);
            }
            else{
                $existingMark->setMark( $form->getData()->getMark());

            }
            
            $manager->flush();

            $this->addFlash(
                'success',
                'Votre note a été prise en compte.'
            );

            return $this->redirectToRoute('recipe_show', ['id' => $recipe->getId()]);
            
        }

        return $this->render('pages/recipe/show.html.twig', [
            'recipe' => $recipe,
            'form' => $form->createView()
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/recette/creation', name: 'recipe_new')]
    public function new(Request $request, EntityManagerInterface $manager): Response
    {
        $recipe = new Recipe();
        $form = $this->createForm(RecipeType::class, $recipe);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $recipe = $form->getData();
            $recipe->setUser($this->getUser());

            $manager->persist($recipe);
            $manager->flush();

            $this->addFlash('success', 'Votre recette a été créé avec succés !');

            return $this->redirectToRoute('recipe');
        };

        return $this->render('pages/recipe/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/recette/edition/{id}', name: 'recipe_edit')]
    #[Security("is_granted('ROLE_USER') and user === recipe.getUser()")]
    public function edit(Recipe $recipe, Request $request, EntityManagerInterface $manager): Response
    {
        $form = $this->createForm(RecipeType::class, $recipe);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $recipe = $form->getData();

            $manager->persist($recipe);
            $manager->flush();

            $this->addFlash('success', 'Votre recette a été modifié avec succés !');

            return $this->redirectToRoute('recipe');
        };

        return $this->render('pages/recipe/edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/recette/suppression/{id}', name: 'recipe_delete')]
    public function delete(Recipe $recipe, EntityManagerInterface $manager): Response
    {
        if(!$recipe){
            $this->addFlash('warning', 'La recette en question n\'a pas été trouvé !');
            return $this->redirectToRoute('recipe');
        }

        $manager->remove($recipe);
        $manager->flush();

        $this->addFlash('success', 'Votre recette a été supprimé avec succés !');

        return $this->redirectToRoute('recipe');
    }
}
