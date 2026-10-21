<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserRoleType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/utilisateurs')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
  #[Route('', name: 'app_admin_user_index', methods: ['GET'])]
  public function index(UserRepository $users): Response
  {
    $comptes = $users->findBy([], ['nom' => 'ASC', 'prenom' => 'ASC']);
    $forms = [];

    foreach ($comptes as $compte) {
      $forms[$compte->getId()] = $this->createForm(UserRoleType::class, null, [
        'role' => $compte->getRolePrincipal(),
        'action' => $this->generateUrl('app_admin_user_role', ['id' => $compte->getId()]),
      ])->createView();
    }

    return $this->render('admin/user/index.html.twig', [
      'comptes' => $comptes,
      'forms' => $forms,
    ]);
  }

  #[Route('/{id}', name: 'app_admin_user_role', methods: ['POST'], requirements: ['id' => '\d+'])]
  public function modifierRole(Request $request, User $compte, EntityManagerInterface $em): Response
  {
    $form = $this->createForm(UserRoleType::class, null, [
      'role' => $compte->getRolePrincipal(),
    ]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $role = $form->get('role')->getData();
      if (is_string($role)) {
        $compte->setRoles([$role]);
        $em->flush();
        $this->addFlash('success', 'Rôle mis à jour.');
      }
    } else {
      $this->addFlash('error', 'Le rôle n\'a pas pu être modifié.');
    }

    return $this->redirectToRoute('app_admin_user_index');
  }
}
