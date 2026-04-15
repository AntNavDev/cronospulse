<?php

declare(strict_types=1);

namespace App\Api;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Abstract base class for external API integrations.
 *
 * Configures a shared HTTP client (base URL, auth, headers) and exposes
 * named get() / post() methods for child classes. API key authentication
 * uses a Bearer token by default — override buildClient() in the child
 * class when a service needs a different scheme (query-param key, HMAC, OAuth).
 */
abstract class ApiConnection
{
    /**
     * The base URL for the API. Must be set by each child class.
     */
    protected string $baseUrl = '';

    /**
     * Optional API key. Sent as a Bearer token when present.
     */
    protected ?string $apiKey;

    /**
     * @param string|null $apiKey Optional API key for authenticated services.
     */
    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Send a GET request to the given endpoint.
     *
     * @param  string               $endpoint Path relative to $baseUrl (e.g. 'query').
     * @param  array<string, mixed> $params   Query string parameters.
     */
    protected function get(string $endpoint, array $params = []): Response
    {
        return $this->buildClient()->get($endpoint, $params);
    }

    /**
     * Send a POST request to the given endpoint.
     *
     * @param  string               $endpoint Path relative to $baseUrl.
     * @param  array<string, mixed> $data     Request body payload.
     */
    protected function post(string $endpoint, array $data = []): Response
    {
        return $this->buildClient()->post($endpoint, $data);
    }

    /**
     * Build and return a pre-configured PendingRequest.
     *
     * Override this in child classes that need a non-standard auth scheme
     * or additional default headers/timeouts.
     */
    protected function buildClient(): PendingRequest
    {
        $client = Http::baseUrl($this->baseUrl)->acceptJson();

        if ($this->apiKey !== null) {
            $client = $client->withToken($this->apiKey);
        }

        return $client;
    }
}
