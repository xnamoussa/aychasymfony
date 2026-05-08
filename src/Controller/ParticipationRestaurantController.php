<?php
namespace App\Controller;
use App\Entity\ParticipationRestaurant;use App\Form\ParticipationRestaurantType;use App\Repository\ParticipationRestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request,Response};use Symfony\Component\Routing\Annotation\Route;
#[Route('/participation-restaurant')]
class ParticipationRestaurantController extends AbstractController {
    #[Route('/',name:'app_participation_restaurant_index')] public function index(ParticipationRestaurantRepository $r):Response{return $this->render('participation_restaurant/index.html.twig',['items'=>$r->findAll()]);}
    #[Route('/new',name:'app_participation_restaurant_new')] public function new(Request $req,EntityManagerInterface $em):Response{$e=new ParticipationRestaurant();$f=$this->createForm(ParticipationRestaurantType::class,$e);$f->handleRequest($req);if($f->isSubmitted()&&$f->isValid()){$em->persist($e);$em->flush();$this->addFlash('success','Créé.');return $this->redirectToRoute('app_participation_restaurant_index');}return $this->render('participation_restaurant/new.html.twig',['form'=>$f->createView()]);}
    #[Route('/{id}',name:'app_participation_restaurant_show',requirements:['id'=>'\d+'])] public function show(ParticipationRestaurant $e):Response{return $this->render('participation_restaurant/show.html.twig',['item'=>$e]);}
    #[Route('/{id}/edit',name:'app_participation_restaurant_edit')] public function edit(Request $req,ParticipationRestaurant $e,EntityManagerInterface $em):Response{$f=$this->createForm(ParticipationRestaurantType::class,$e);$f->handleRequest($req);if($f->isSubmitted()&&$f->isValid()){$em->flush();$this->addFlash('success','Modifié.');return $this->redirectToRoute('app_participation_restaurant_index');}return $this->render('participation_restaurant/edit.html.twig',['form'=>$f->createView(),'item'=>$e]);}
    #[Route('/{id}/delete',name:'app_participation_restaurant_delete',methods:['POST'])] public function delete(Request $req,ParticipationRestaurant $e,EntityManagerInterface $em):Response{if($this->isCsrfTokenValid('delete'.$e->getId(),$req->request->get('_token'))){$em->remove($e);$em->flush();$this->addFlash('success','Supprimé.');}return $this->redirectToRoute('app_participation_restaurant_index');}
}
