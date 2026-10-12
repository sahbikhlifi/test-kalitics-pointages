<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Clocking;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/clockings/{id}', requirements: ['id' => '\d+'])]
class ClockingItemController extends AbstractController
{
    /**
     * Suppression en POST + jeton CSRF : un simple lien GET pouvait être déclenché
     * par un préchargement de navigateur ou une page tierce.
     *
     * @param \Doctrine\ORM\EntityManagerInterface      $entityManager
     * @param \App\Entity\Clocking                      $clocking
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route('/delete', name: 'app_Clocking_delete', methods: ['POST'])]
    public function deleteClocking(
        EntityManagerInterface $entityManager,
        Clocking $clocking,
        Request $request,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_clocking_' . $clocking->getId(), $request->request->getString('_token'))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }

        $entityManager->remove($clocking);
        $entityManager->flush();

        // Retour sur la liste avec les mêmes filtres et la même page.
        parse_str($request->request->getString('_list_query'), $listQuery);
        $listQuery = array_intersect_key($listQuery, array_flip(['user', 'project', 'from', 'to', 'page']));

        return $this->redirectToRoute('app_Clocking_list', array_filter($listQuery, 'is_string'));
    }
}
