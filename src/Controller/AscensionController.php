<?php

namespace App\Controller;

use App\Entity\Ascension;
use App\Entity\Bloc;
use App\Entity\User;
use App\Form\AscensionType;
use App\Repository\AscensionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_GRIMPEUR')]
final class AscensionController extends AbstractController
{
  #[Route('/blocs/{id}/ascensions', name: 'app_ascension_create', methods: ['POST'], requirements: ['id' => '\d+'])]
  public function creer(
    Request $request,
    Bloc $bloc,
    EntityManagerInterface $em,
    AscensionRepository $ascensions,
  ): Response {
    $grimpeur = $this->getUser();
    if (!($grimpeur instanceof User)) {
      throw $this->createAccessDeniedException();
    }

    $ascension = new Ascension();
    $ascension->setBloc($bloc);
    $ascension->setGrimpeur($grimpeur);
    $form = $this->createForm(AscensionType::class, $ascension, ['bloc' => $bloc]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $em->persist($ascension);
      $em->flush();
      $this->addFlash('success', 'Réussite enregistrée.');

      return $this->redirectToRoute('app_bloc_show', ['id' => $bloc->getId()]);
    }

    return $this->render('bloc/show.html.twig', [
      'bloc' => $bloc,
      'ascensionForm' => $form,
      'miennes' => $ascensions->findParGrimpeurEtBloc($grimpeur, $bloc),
    ]);
  }

  #[Route('/mes-ascensions', name: 'app_ascension_index', methods: ['GET'])]
  public function index(AscensionRepository $ascensions): Response
  {
    $grimpeur = $this->getUser();
    if (!($grimpeur instanceof User)) {
      throw $this->createAccessDeniedException();
    }

    return $this->render('ascension/index.html.twig', [
      'ascensions' => $ascensions->findParGrimpeur($grimpeur),
    ]);
  }

  #[Route('/ascensions/{id}/supprimer', name: 'app_ascension_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
  public function supprimer(Request $request, Ascension $ascension, EntityManagerInterface $em): Response
  {
    $this->denyAccessUnlessGranted('ASCENSION_DELETE', $ascension);

    if (!$this->isCsrfTokenValid('supprimer' . $ascension->getId(), $request->request->getString('_token'))) {
      throw $this->createAccessDeniedException('Jeton invalide.');
    }

    $em->remove($ascension);
    $em->flush();
    $this->addFlash('success', 'Réussite supprimée.');

    return $this->redirectToRoute('app_ascension_index');
  }
}
