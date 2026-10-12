<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\ClockingFilterType;
use App\Form\CreateClockingType;
use App\Form\Model\ClockingFilter;
use App\Form\Model\ClockingRequest;
use App\Repository\ClockingRepository;
use App\Service\ClockingCreator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/clockings')]
class ClockingCollectionController extends AbstractController
{
    private const PER_PAGE = 25;

    /**
     * @param \App\Service\ClockingCreator                       $clockingCreator
     * @param \Symfony\Component\HttpFoundation\Request          $request
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translator
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route('/create', name: 'app_Clocking_create', methods: [
        'GET',
        'POST',
    ])]
    public function createClocking(
        ClockingCreator $clockingCreator,
        Request $request,
        TranslatorInterface $translator,
    ): Response {
        $form = $this->createForm(CreateClockingType::class, ClockingRequest::createDefault());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $clockings = $clockingCreator->create($form->getData());

            $this->addFlash('success', $translator->trans('clocking.flash.created', [
                '%count%' => count($clockings),
            ]));

            return $this->redirectToRoute('app_Clocking_list');
        }

        return $this->render('app/Clocking/create.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @param \App\Repository\ClockingRepository        $clockingRepository
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route('', name: 'app_Clocking_list', methods: ['GET'])]
    public function listClockings(
        ClockingRepository $clockingRepository,
        Request $request,
    ): Response {
        $filter = new ClockingFilter();
        $form = $this->createForm(ClockingFilterType::class, $filter);
        $form->handleRequest($request);

        // Filtres invalides (ex. période inversée) : on affiche l'erreur et la liste non filtrée.
        if ($form->isSubmitted() && !$form->isValid()) {
            $filter = new ClockingFilter();
        }

        $listPage = $clockingRepository->findPageForListing($filter, $request->query->getInt('page', 1), self::PER_PAGE);

        return $this->render('app/Clocking/list.html.twig', [
            // createView() : un filtre invalide reste une page 200 (pas de 422 automatique).
            'filterForm' => $form->createView(),
            'filter' => $filter,
            'listPage' => $listPage,
        ]);
    }
}
