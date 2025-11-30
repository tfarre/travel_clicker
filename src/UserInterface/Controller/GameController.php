<?php

declare(strict_types=1);

namespace App\UserInterface\Controller;

use App\Domain\Config\GameConfigLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Game Controller - Main game page
 *
 * Loads the game configuration and passes it to the Svelte frontend.
 */
final class GameController extends AbstractController
{
    public function __construct(
        private readonly GameConfigLoader $configLoader,
    ) {
    }

    #[Route('/game', name: 'app_game', methods: ['GET'])]
    public function index(): Response
    {
        $config = $this->configLoader->load();

        return $this->render('game/index.html.twig', [
            'gameConfig' => $config->toArray(),
        ]);
    }
}
