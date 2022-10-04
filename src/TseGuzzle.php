<?php

namespace Tsetsee\TseGuzzle;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleLogMiddleware\LogMiddleware;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class TseGuzzle
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

        if (!empty($config['oauth2'])) {
            $handler->push(function (callable $handler) use ($config) {
                return function (RequestInterface $request, array $options) use ($handler, $config) {
                    if (!empty($options['oauth2'])) {
                        $method = $config['oauth2'] ?? 'bearer';
                        switch($method) {
                            case 'custom_header':
                                $request = $request->withHeader(
                                    $config['oauth2_custom_header'],
                                    $this->getAccessToken()
                                );
                                break;
                            case 'bearer':
                            default:
                                $request = $request->withHeader(
                                    'Authorization',
                                    'Bearer '.$this->getAccessToken()
                                );
                        }
                    }

                    return $handler($request, $options);
                };
            });
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
}
