<?php

namespace VendWeave\Gateway\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use VendWeave\Gateway\Exceptions\ApiConnectionException;
use VendWeave\Gateway\Exceptions\InvalidCredentialsException;

/**
 * HTTP client for VendWeave POS API communication.
 * 
 * This service handles all direct API communication with the POS system.
 * It manages authentication headers, request/response cycles, and error handling.
 */
class VendWeaveApiClient
{
    private Client $client;
    private string $endpoint;
    private ?string $apiKey;
    private ?string $apiSecret;
    private ?int $storeId;

    public function __construct(
        string $endpoint,
        ?string $apiKey,
        ?string $apiSecret,
        ?int $storeId
    ) {
        $this->endpoint = rtrim($endpoint, '/');
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->storeId = $storeId;

        $this->client = new Client([
            'base_uri' => $this->endpoint,
            'timeout' => 30,
            'connect_timeout' => 10,
            'http_errors' => false,
        ]);
    }

    /**
     * Verify a transaction against the POS API.
     *
     * @param string $orderId
     * @param float $amount
     * @param string $paymentMethod
     * @param string|null $trxId
     * @return array Raw API response data
     * @throws ApiConnectionException
     * @throws InvalidCredentialsException
     */
    public function verifyTransaction(
        string $orderId,
        float $amount,
        string $paymentMethod,
        ?string $trxId = null
    ): array {
        $this->validateCredentials();

        $params = [
            'order_id' => $orderId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
        ];

        if ($trxId !== null) {
            $params['trx_id'] = $trxId;
        }

        return $this->request('GET', '/transactions/verify', $params);
    }

    /**
     * Get the configured store ID.
     */
    public function getStoreId(): ?int
    {
        return $this->storeId;
    }

    /**
     * Make an authenticated request to the POS API.
     *
     * @param string $method HTTP method
     * @param string $path API path
     * @param array $params Query parameters or body data
     * @return array Decoded response data
     * @throws ApiConnectionException
     * @throws InvalidCredentialsException
     */
    private function request(string $method, string $path, array $params = []): array
    {
        $options = [
            'headers' => $this->getAuthHeaders(),
        ];

        if ($method === 'GET') {
            $options['query'] = $params;
        } else {
            $options['json'] = $params;
        }

        $this->log('info', 'VendWeave API Request', [
            'method' => $method,
            'path' => $path,
            'params' => $this->sanitizeForLog($params),
        ]);

        try {
            $response = $this->client->request($method, $path, $options);
            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true) ?? [];

            $this->log('info', 'VendWeave API Response', [
                'status_code' => $statusCode,
                'response' => $this->sanitizeForLog($data),
            ]);

            if ($statusCode === 401) {
                throw new InvalidCredentialsException('API authentication failed');
            }

            if ($statusCode >= 500) {
                throw new ApiConnectionException(
                    'POS API returned server error: ' . ($data['message'] ?? 'Unknown error')
                );
            }

            return array_merge($data, ['_http_status' => $statusCode]);

        } catch (ConnectException $e) {
            $this->log('error', 'VendWeave API Connection Failed', [
                'error' => $e->getMessage(),
            ]);
            throw new ApiConnectionException(
                'Unable to connect to VendWeave POS API: ' . $e->getMessage(),
                $e
            );
        } catch (RequestException $e) {
            $this->log('error', 'VendWeave API Request Failed', [
                'error' => $e->getMessage(),
            ]);
            throw new ApiConnectionException(
                'API request failed: ' . $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Get authentication headers for API requests.
     */
    private function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'X-Store-Secret' => $this->apiSecret,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Validate that API credentials are configured.
     *
     * @throws InvalidCredentialsException
     */
    private function validateCredentials(): void
    {
        if (empty($this->apiKey)) {
            throw new InvalidCredentialsException('VENDWEAVE_API_KEY is not configured');
        }

        if (empty($this->apiSecret)) {
            throw new InvalidCredentialsException('VENDWEAVE_API_SECRET is not configured');
        }

        if (empty($this->storeId)) {
            throw new InvalidCredentialsException('VENDWEAVE_STORE_ID is not configured');
        }
    }

    /**
     * Log API interactions if logging is enabled.
     */
    private function log(string $level, string $message, array $context = []): void
    {
        if (!config('vendweave.logging.enabled', true)) {
            return;
        }

        $channel = config('vendweave.logging.channel', 'stack');

        Log::channel($channel)->$level("[VendWeave] {$message}", $context);
    }

    /**
     * Sanitize sensitive data for logging.
     */
    private function sanitizeForLog(array $data): array
    {
        $sensitive = ['api_key', 'api_secret', 'secret', 'password'];

        return array_map(function ($value, $key) use ($sensitive) {
            if (is_string($key) && in_array(strtolower($key), $sensitive)) {
                return '***REDACTED***';
            }
            return $value;
        }, $data, array_keys($data));
    }
}
