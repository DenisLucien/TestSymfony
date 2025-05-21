<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;

final class TestController extends AbstractController
{
    #[Route("/", name: "TestController")]
    function index(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        // $user = new User();
        // $user
        //     ->setEmail("john@doe.fr")
        //     ->setUsername("JohnDoe")
        //     ->setPassword($hasher->hashPassword($user, "0000"))
        //     ->setRoles([]);
        // $em->persist($user);
        // $em->flush();
        return $this->render("test/index.html.twig");
    }
}
