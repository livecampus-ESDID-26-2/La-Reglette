<?php

namespace App\Controller;

use App\Entity\Ascension;
use App\Entity\Bloc;
use App\Entity\User;
use App\Form\AscensionType;
use App\Form\BlocFilterType;
use App\Form\BlocType;
use App\Model\BlocFiltre;
use App\Repository\AscensionRepository;
use App\Repository\BlocRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/blocs')]
#[IsGranted('ROLE_GRIMPEUR')]
final class BlocController extends AbstractController
{
  #[Route('', name: 'app_bloc_index', methods: ['GET'])]
  public function index(Request $request, BlocRepository $blocs): Response
  {
    $filtre = new BlocFiltre();
    $form = $this->createForm(BlocFilterType::class, $filtre);
    $form->handleRequest($request);

    return $this->render('bloc/index.html.twig', [
      'filtreForm' => $form,
      'blocs' => $form->isSubmitted() && !$form->isValid() ? [] : $blocs->findEnPlace($filtre),
    ]);
  }

  #[Route('/a-reussir', name: 'app_bloc_a_reussir', methods: ['GET'])]
  public function aReussir(BlocRepository $blocs): Response
  {
    $grimpeur = $this->getUser();
    if (!($grimpeur instanceof User)) {
      throw $this->createAccessDeniedException();
    }

    return $this->render('bloc/a_reussir.html.twig', [
      'blocs' => $blocs->findEnPlaceNonReussisPar($grimpeur),
    ]);
  }

  #[Route('/nouveau', name: 'app_bloc_new', methods: ['GET'])]
  #[IsGranted('ROLE_OUVREUR')]
  public function nouveau(): Response
  {
    $bloc = $this->nouveauBloc();
    $form = $this->createForm(BlocType::class, $bloc, [
      'action' => $this->generateUrl('app_bloc_create'),
    ]);

    return $this->render('bloc/form.html.twig', [
      'bloc' => $bloc,
      'form' => $form,
    ]);
  }

  #[Route('', name: 'app_bloc_create', methods: ['POST'])]
  #[IsGranted('ROLE_OUVREUR')]
  public function creer(Request $request, EntityManagerInterface $em): Response
  {
    $bloc = $this->nouveauBloc();
    $form = $this->createForm(BlocType::class, $bloc, [
      'action' => $this->generateUrl('app_bloc_create'),
    ]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $em->persist($bloc);
      $em->flush();
      $this->addFlash('success', 'Bloc ouvert.');

      return $this->redirectToRoute('app_bloc_show', ['id' => $bloc->getId()]);
    }

    return $this->render('bloc/form.html.twig', [
      'bloc' => $bloc,
      'form' => $form,
    ]);
  }

  #[Route('/{id}', name: 'app_bloc_show', methods: ['GET'], requirements: ['id' => '\d+'])]
  public function show(Bloc $bloc, AscensionRepository $ascensions): Response
  {
    $grimpeur = $this->getUser();
    if (!($grimpeur instanceof User)) {
      throw $this->createAccessDeniedException();
    }

    $ascension = new Ascension();
    $ascension->setDateReussite(new \DateTimeImmutable('today'));

    return $this->render('bloc/show.html.twig', [
      'bloc' => $bloc,
      'ascensionForm' => $this->createForm(AscensionType::class, $ascension, [
        'bloc' => $bloc,
        'action' => $this->generateUrl('app_ascension_create', ['id' => $bloc->getId()]),
      ]),
      'miennes' => $ascensions->findParGrimpeurEtBloc($grimpeur, $bloc),
    ]);
  }

  #[Route('/{id}/modifier', name: 'app_bloc_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
  public function modifier(Request $request, Bloc $bloc, EntityManagerInterface $em): Response
  {
    $this->denyAccessUnlessGranted('BLOC_EDIT', $bloc);
    $form = $this->createForm(BlocType::class, $bloc);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $em->flush();
      $this->addFlash('success', 'Bloc modifié.');

      return $this->redirectToRoute('app_bloc_show', ['id' => $bloc->getId()]);
    }

    return $this->render('bloc/form.html.twig', [
      'bloc' => $bloc,
      'form' => $form,
    ]);
  }

  #[Route('/{id}/demonter', name: 'app_bloc_demonter', methods: ['POST'], requirements: ['id' => '\d+'])]
  public function demonter(Request $request, Bloc $bloc, EntityManagerInterface $em): Response
  {
    $this->denyAccessUnlessGranted('BLOC_DEMONTER', $bloc);

    if (!$this->isCsrfTokenValid('demonter' . $bloc->getId(), $request->request->getString('_token'))) {
      throw $this->createAccessDeniedException('Jeton invalide.');
    }

    if ($bloc->isEnPlace()) {
      $bloc->setDateDemontage(new \DateTimeImmutable());
      $em->flush();
      $this->addFlash('success', 'Bloc démonté.');
    }

    return $this->redirectToRoute('app_bloc_show', ['id' => $bloc->getId()]);
  }

  private function nouveauBloc(): Bloc
  {
    $ouvreur = $this->getUser();
    if (!($ouvreur instanceof User)) {
      throw $this->createAccessDeniedException();
    }

    $bloc = new Bloc();
    $bloc->setOuvreur($ouvreur);
    $bloc->setDateOuverture(new \DateTimeImmutable('today'));

    return $bloc;
  }
}
