<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PokemonController extends AbstractController
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route('/api/pokemon/search', name: 'pokemon_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        if (strlen($query) < 2) {
            return new JsonResponse([], 200);
        }

        try {
            // Use wildcard * for partial matches
            $response = $this->client->request('GET', 'https://api.pokemontcg.io/v2/cards', [
                'query' => [
                    'q' => "name:*$query*",
                    'pageSize' => 3
                ],
                'timeout' => 10,
                'verify_peer' => false,
                'verify_host' => false,
            ]);

            $data = $response->toArray(false)['data'] ?? [];

            $results = array_map(fn($card) => [
                'id' => $card['id'] ?? null,
                'name' => $card['name'] ?? 'Unknown',
                'image' => $card['images']['small'] ?? null,
                'price' => $card['cardmarket']['prices']['averageSellPrice'] ?? null,
            ], $data);

            return new JsonResponse($results);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
