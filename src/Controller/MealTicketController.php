<?php
namespace App\Controller;
use App\Entity\MealTicket;use App\Form\MealTicketType;use App\Repository\MealTicketRepository;
use Doctrine\ORM\EntityManagerInterface;use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request,Response};use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/meal-ticket')]
#[IsGranted('ROLE_USER')]
class MealTicketController extends AbstractController {
    #[Route('/',name:'app_meal_ticket_index')] public function index(MealTicketRepository $r):Response{return $this->render('meal_ticket/index.html.twig',['items'=>$r->findAll()]);}
    #[Route('/new',name:'app_meal_ticket_new')] public function new(Request $req,EntityManagerInterface $em):Response{$e=new MealTicket();$f=$this->createForm(MealTicketType::class,$e);$f->handleRequest($req);if($f->isSubmitted()&&$f->isValid()){$em->persist($e);$em->flush();$this->addFlash('success','Meal Ticket créé.');return $this->redirectToRoute('app_meal_ticket_index');}return $this->render('meal_ticket/new.html.twig',['form'=>$f->createView()]);}
    #[Route('/{id}',name:'app_meal_ticket_show',requirements:['id'=>'\d+'])] public function show(MealTicket $e):Response{return $this->render('meal_ticket/show.html.twig',['item'=>$e]);}
    #[Route('/{id}/edit',name:'app_meal_ticket_edit')] public function edit(Request $req,MealTicket $e,EntityManagerInterface $em):Response{$f=$this->createForm(MealTicketType::class,$e);$f->handleRequest($req);if($f->isSubmitted()&&$f->isValid()){$em->flush();$this->addFlash('success','Modifié.');return $this->redirectToRoute('app_meal_ticket_index');}return $this->render('meal_ticket/edit.html.twig',['form'=>$f->createView(),'item'=>$e]);}
    #[Route('/{id}/delete',name:'app_meal_ticket_delete',methods:['POST'])] public function delete(Request $req,MealTicket $e,EntityManagerInterface $em):Response{if($this->isCsrfTokenValid('delete'.$e->getId(),$req->request->get('_token'))){$em->remove($e);$em->flush();$this->addFlash('success','Supprimé.');}return $this->redirectToRoute('app_meal_ticket_index');}
}
