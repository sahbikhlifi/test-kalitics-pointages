<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/projects')]
class ProjectCollectionController extends AbstractController
{
    /**
     * @param \App\Repository\ProjectRepository $projectRepository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route('', name: 'app_Project_list', methods: ['GET'])]
    public function listProjects(ProjectRepository $projectRepository): Response
    {
        $projects = $projectRepository->findAll();

        return $this->render('app/Project/list.html.twig', [
            'projects' => $projects,
        ]);
    }
}
