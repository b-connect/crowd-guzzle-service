<?php
/**
 * @file contains rest client
 * 
 * Restclient.
 */
namespace bconnect\crowd\api;

use GuzzleHttp\Client;
use GuzzleHttp\Command\Guzzle\Description;
use GuzzleHttp\Command\Guzzle\GuzzleClient;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Cookie\SessionCookieJar;

/**
 * Client.
 */
class CrowdClient extends GuzzleClient {
    public static function create($config = []) {
        $handler_stack = HandlerStack::create();
        // Add an authentication handler to the stack.
        $handler_stack->push(function (callable $handler) use ($config) {
            return function (RequestInterface $request, array $options) use ($handler, $config) {
                // Return the request with a Bearer authorization header.
                return $handler(
                    $request
                        ->withAddedHeader('Authorization', 'Basic ' .  base64_encode(implode(':', [$config['user'], $config['pass']]) ))
                        ->withAddedHeader('Accept', 'application/json')
                        ->withAddedHeader('Content-Type', 'application/json')
                        ->withAddedHeader('X-Atlassian-Token', 'no-check'),
                    $options
                );
            };
        }, 'auth');
        // Load the service description file.
        $service_description = new Description(
            ['baseUrl' => $config['base_uri']] + (array) json_decode(file_get_contents(__DIR__ . '/../json/service.json'), TRUE)
        );
        $jar = new SessionCookieJar('crowd_cookie_jar', true);
        if (!$config['debug']) {
            $config['debug'] = false;
        }
        $client = new Client(['handler'=>$handler_stack, 'cookies' => $jar, 'debug' => $config['debug']]);
        return new static($client, $service_description, NULL, NULL, NULL, $config);
    }
}