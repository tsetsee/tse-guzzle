<?php

namespace Tsetsee\TseGuzzle;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleLogMiddleware\LogMiddleware;
use InvalidArgumentException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class TseGuzzle implements ClientInterface
{
    protected Client $client;
    /**
    * @param array<string, mixed> $config
    */
    public function __construct(private array $config = [])
    {
        $handler = $config['handler'] ?? null;

        if (!$handler) {
            $handler = HandlerStack::create();
        }

        if (isset($config['oauth2'])) {
            $oauth2 = $config['oauth2'] ?? 'bearer';
            $authHeader = $oauth2 === 'custom_header' ? $config['oauth2_custom_header'] ?? null : 'Authorization';
            $handler->push(function (callable $handler) use ($oauth2, $authHeader) {
                return function (RequestInterface $request, array $options) use ($handler, $oauth2, $authHeader) {
                    if (!empty($options['oauth2'])) {
                        switch($oauth2) {
                            case 'custom_header':
                                $request = $request->withHeader(
                                    $authHeader,
                                    $this->getAccessToken()
                                );
                                break;
                            case 'bearer':
                            default:
                                $request = $request->withHeader(
                                    $authHeader,
                                    'Bearer '.$this->getAccessToken()
                                );
                        }
                    }

                    return $handler($request, $options);
                };
            });

            unset($config['oauth2']);
        }

        if (!empty($config['logger'])) {
            if ($config['logger'] instanceof LoggerInterface) {
                $handler->push(new LogMiddleware($config['logger']));
            } else {
                throw new InvalidArgumentException('logger argument is not '.LoggerInterface::class);
            }
        }

        $config['handler'] = $handler;

        $this->client = new Client($config);
    }

    protected function getAccessToken(): string
    {
        if (!empty($this->config['oauth2'])) {
            throw new RuntimeException('Must implement getAccessToken method');
        }

        return '';
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->client->sendRequest($request);
    }
}
