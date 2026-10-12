<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    /**
     * @param \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface $parameterBag
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function home(ParameterBagInterface $parameterBag): Response
    {
        $projectDir = $parameterBag->get('kernel.project_dir');
        $readmeContent = file_get_contents("$projectDir/README.md");

        return $this->render('app/home.html.twig', [
            'readmeContent' => $readmeContent,
        ]);
    }
}
