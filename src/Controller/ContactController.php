<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact')]
    public function index(Request $request, EntityManagerInterface $manager, MailerInterface $mailer): Response
    {
        $contact = new Contact();

        if($this->getUser()){
            $contact->setFullName($this->getUser()->getFullName())
                    ->setEmail($this->getUser()->getEmail());
        }

        $form = $this->createForm(ContactType::class, $contact);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $contact = $form->getData();

            $manager->persist($contact);
            $manager->flush();

            $email = (new TemplatedEmail())
            ->from($contact->getEmail())
            ->to('admin@symrecipe.com')
            ->subject($contact->getSubject())
            ->htmlTemplate('emails/contact.html.twig')
            
            //pass variable (name => valude) to the template
            ->context([
                'contact' => $contact,
            ]);

            $mailer->send($email);        

            $this->addFlash(
                'success', 
                'Votre demande a été envoyé avec succés !'
            );

            return $this->redirectToRoute('contact');
        }

        return $this->render('pages/contact/index.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
