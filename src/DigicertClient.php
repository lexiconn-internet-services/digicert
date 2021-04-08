<?php

namespace LexiConnInternetServices\DigiCert;

use Carbon\Carbon;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use LexiConnInternetServices\DigiCert\Exceptions\MissingApiKeyException;
use LexiConnInternetServices\DigiCert\Facades\RateLimiter;

/**
 * Class Client
 *
 * @package LexiConnInternetServices\DigiCert
 *
 */
class DigicertClient
{
    private string $apiKey;
    private string $apiBaseUrl;
    private array  $headers;
    private array  $json;
    private string $body;
    private ?array $parameters;
    private array  $queries = [];
    private string $requestUri;
    private string $requestMethod;
    private array  $options;
    
    /**
     * Client constructor.
     *
     * @param  string|null  $apiBaseUrl
     * @param  string|null  $apiKey
     *
     * @throws MissingApiKeyException
     */
    public function __construct(string $apiBaseUrl = null, string $apiKey = null)
    {
        if (!config('digicert.api_key', $apiKey)) {
            throw new MissingApiKeyException();
        }
        $this->addHeader('X-DC-DEVKEY', $apiKey ?? config('digicert.api_key'));
        $this->addHeader('Content-Type', 'application/json');
        $this->apiBaseUrl = $apiBaseUrl ?? config('digicert.url', 'https://www.digicert.com/services/v2');
    }
    
    /**
     * @param  string  $name
     * @param  string  $value
     *
     * @return DigicertClient
     */
    public function addHeader(string $name, string $value): DigicertClient
    {
        $this->headers[$name] = $value;
        
        return $this;
    }
    
    /**
     * @param  string  $name
     * @param  Carbon|string  $value
     * @param  Carbon|null  $value2
     * @param  string|null  $operator
     *
     * @return DigicertClient
     */
    public function addFilter(string $name, $value, Carbon $value2 = null, $operator = null): DigicertClient
    {
        if ($value instanceof Carbon) {
            $value = $value->format('Y - m - d\+00:00:00');
            if ($value2) {
                $value .= '...';
                $value .= $value2->format('Y - m - d\+00:00:00');
            }
        }
        
        if ($operator) {
            $value = $operator.$value;
        }
        
        return $this->addParameter("filters[$name]", $value);
    }
    
    /**
     * @param  string  $name
     * @param $value
     *
     * @return DigicertClient
     */
    public function addParameter(string $name, $value): DigicertClient
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }
        $this->parameters[$name] = $value;
        
        return $this;
    }
    
    /**
     * Creates a GraphQL query and adds it to the list.
     *
     * @see https://dev.digicert.com/custom-reports-api/ Documentation of available GraphQL functions.
     *
     * @param  string  $function
     * @param  array  $parameters
     * @param  array  $fields
     * @param  string|null  $name
     */
    public function addQuery(string $function, array $parameters, array $fields, string $name = null): DigicertClient
    {
        array_walk($parameters, function (&$val, $key) {
            if ($val instanceof Carbon) {
                $val = $val->format('Y-m-d\+00:00:00');
            }
            $val = "$key:".json_encode($val);
        });
        
        $query = $name ?? ($function.'_'.sizeof($this->queries)).': ';
        $query .= $function.'(';
        $query .= implode(',', $parameters);
        $query .= '){';
        $query .= implode(',', $fields);
        $query .= '}';
        
        $this->queries[sizeof($this->queries)] = $query;
    }
    
    /**
     * Executes the API request and returns an object representing the JSON response.
     *
     * @return object
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function execute(): object
    {
        $this->buildRequest();
        $client = new HttpClient();
        Log::debug('Creating Digicert Request', [
            'Method'     => $this->requestMethod,
            'RequestURI' => $this->requestUri,
            'Options'    => $this->options,
        ]);
        RateLimiter::checkLimit();
        $request = $client->request($this->requestMethod, $this->requestUri, $this->options);
        
        return json_decode($request->getBody());
    }
    
    /**
     * Builds the request parameters
     */
    private function buildRequest(): void
    {
        if (isset($this->parameters) && !Str::contains($this->requestUri, '?')) {
            $this->requestUri .= '?';
            $params           = $this->parameters;
            array_walk($params, function (&$a, $b) {
                $a = "$b=$a";
            });
            $this->requestUri .= implode('&', $params);
        }
        
        if (isset($this->queries) && sizeof($this->queries) > 0) {
            $this->addJsonParam('query', '{'.implode(',', $this->queries).'}');
        }
        
        $this->options['headers'] = $this->headers;
        
        if (isset($this->json)) {
            $this->options['json'] = $this->json;
        }
        if (isset($this->body)) {
            $this->options['body'] = $this->body;
        }
    }
    
    /**
     * Adds a JSON parameter
     *
     * @param  string  $name
     * @param $value
     *
     * @return DigicertClient
     */
    public function addJsonParam(string $name, $value): DigicertClient
    {
        $this->json[$name] = $value;
        
        return $this;
    }
    
    /**
     * Starts a GraphQL query
     *
     * @return DigicertClient
     */
    public function startQuery(): DigicertClient
    {
        return $this->endpoint('reports/query', 'POST');
    }
    
    /**
     * Sets the API endpoint and request method
     *
     * @see https://dev.digicert.com/services-api/ Documentation of available API endpoints
     *
     * @param  string  $endpoint
     * @param  string  $method
     *
     * @return DigicertClient
     *
     */
    public function endpoint(string $endpoint, string $method = 'GET'): DigicertClient
    {
        $endpoint   = Str::start($endpoint, '/');
        $requestUri = $this->apiBaseUrl.$endpoint;
        
        $this->requestUri    = $requestUri;
        $this->requestMethod = $method;
        
        return $this;
    }
}
