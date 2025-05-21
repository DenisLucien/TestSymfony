<?php

namespace App\Controller;

use App\DTO\ContactDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ContactType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class ContactController extends AbstractController
{
    #[Route("/contact", name: "contact")]
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        $data = new ContactDTO();

        $data->name = "John Doe";
        $data->email = "John@doe.fr";
        $data->message = "Super site";

        $form = $this->createForm(ContactType::class, $data);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $mail = (new TemplatedEmail())
                ->from($data->email)
                ->to($data->service)
                ->htmlTemplate("emails/contact.html.twig")
                ->subject("Demande de contact")
                ->context(["data" => $data]);

            try {
                $mailer->send($mail);
            } catch (\Exception $e) {
                $this->addFlash("danger", 'Impossible d\'envoyer votre email');
            }

            $this->addFlash("success", "Votre email a bien été envoyé");
            return $this->redirectToRoute("contact");
        }
        return $this->render("contact/contact.html.twig", [
            "form" => $form->createView(),
        ]);
    }
}
