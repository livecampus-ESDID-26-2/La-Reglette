<?php

namespace App\Controller\Admin;

use App\Entity\Secteur;
use App\Form\SecteurType;
use App\Repository\SecteurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/secteurs')]
#[IsGranted('ROLE_ADMIN')]
final class SecteurController extends AbstractController
{
  #[Route('', name: 'app_admin_secteur_index', methods: ['GET', 'POST'])]
  public function index(Request $request, SecteurRepository $secteurs, EntityManagerInterface $em): Response
  {
    $secteur = new Secteur();
    $form = $this->createForm(SecteurType::class, $secteur);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $em->persist($secteur);
      $em->flush();
      $this->addFlash('success', 'Secteur créé.');

      return $this->redirectToRoute('app_admin_secteur_index');
    }

    return $this->render('admin/secteur/index.html.twig', [
      'secteurs' => $secteurs->findBy([], ['nom' => 'ASC']),
      'form' => $form,
    ]);
  }

  #[Route('/{id}/modifier', name: 'app_admin_secteur_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
  public function modifier(Request $request, Secteur $secteur, EntityManagerInterface $em): Response
  {
    $form = $this->createForm(SecteurType::class, $secteur);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $em->flush();
      $this->addFlash('success', 'Secteur modifié.');

      return $this->redirectToRoute('app_admin_secteur_index');
    }

    return $this->render('admin/secteur/form.html.twig', [
      'secteur' => $secteur,
      'form' => $form,
    ]);
  }
}
