<?php

namespace TaraPayment;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use RuntimeException;

class TaraClient
{
    private string $apiKey;
    private string $businessId;
    private string $webhookUrl;
    private string $defaultCountryCode;
    private Client $httpClient;

    public function __construct(string $apiKey, string $businessId, string $webhookUrl = '', string $defaultCountryCode = '237')
    {
        if (empty($apiKey) || empty($businessId)) {
            throw new InvalidArgumentException("❌ L'API Key et le Business ID sont obligatoires.");
        }

        $this->apiKey = $apiKey;
        $this->businessId = $businessId;
        $this->webhookUrl = $webhookUrl;
        $this->defaultCountryCode = $defaultCountryCode;

        $this->httpClient = new Client([
            'base_uri' => 'https://www.dklo.co/api/tara/',
            'headers'  =>[
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json'
            ]
        ]);
    }

    /**
     * Nettoie et formate le numéro de téléphone (enlève le +, ajoute l'indicatif si manquant)
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Supprime les espaces et le '+'
        $phone = str_replace([' ', '+'], '', $phone);
        
        // Si le numéro a 9 chiffres (ex: 655251245 au Cameroun), on ajoute l'indicatif
        if (strlen($phone) === 9) {
            return $this->defaultCountryCode . $phone;
        }
        
        return $phone;
    }

    /**
     * Initie un paiement en cachant la complexité
     */
    public function initPayment(array $data): array
    {
        // 1. MAGIE : Génération stricte du "product-xxxx"
        // uniqid() génère une chaîne de 13 caractères basée sur le temps (ex: 64b8a92f2c8d1)
        $uniqueProductId = 'product-' . uniqid();

        // 2. MAGIE : Formatage automatique du téléphone
        $formattedPhone = $this->formatPhoneNumber($data['phoneNumber'] ?? '');

        $webhook = $data['webhookUrl'] ?? $this->webhookUrl;
        if (empty($webhook)) {
            throw new InvalidArgumentException("❌ Aucune URL de Webhook définie.");
        }

        $payload =[
            'apiKey'       => $this->apiKey,
            'businessId'   => $this->businessId,
            'productId'    => $uniqueProductId, // Invisible pour le développeur
            'productName'  => $data['productName'] ?? 'Paiement',
            'network'      => $data['network'] ?? '',
            'productPrice' => (int) $data['price'],
            'phoneNumber'  => $formattedPhone,
            'webHookUrl'   => $webhook
        ];

        try {
            $response = $this->httpClient->post('mobilepay',[
                'json' => $payload
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (GuzzleException $e) {
            // Traduction des erreurs Guzzle en erreurs simples pour le débutant
            throw new RuntimeException("Erreur lors de la communication avec Taramoney : " . $e->getMessage());
        }
    }

    /**
     * Helper pour lire la donnée Webhook entrante
     */
    public function parseWebhook(string $jsonPayload): array
    {
        $data = json_decode($jsonPayload, true);
        if (!$data) {
            throw new InvalidArgumentException("❌ Payload Webhook invalide (JSON malformé).");
        }
        return $data;
    }
}