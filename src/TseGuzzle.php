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
    protected Client $guzzle;
    /**
    * @param array<string, mixed> $config
    */
    public function __construct(private array $config = [])
    {
        $handler = $config['handler'] ?? null;

        if (!$handler) {
            $handler = HandlerStack::create();
        }

        $handler->push(function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                if (!empty($options['oauth2'])) {
                    $request = $request->withHeader('Authorization', 'Bearer '.$this->getAccessToken());
                }

                return $handler($request, $options);
            };
        });

        if (!empty($config['logger'])) {
            if ($config['logger'] instanceof LoggerInterface) {
                $handler->push(new LogMiddleware($config['logger']));
            } else {
                throw new InvalidArgumentException('logger argument is not '.LoggerInterface::class);
            }
        }

        $config['handler'] = $handler;

        $this->guzzle = new Client($config);
    }

    protected function getAccessToken(): string
    {
        if (!empty($this->config['oauth2'])) {
            throw new RuntimeException('Must implement getAccessToken method');
        }

        return '';
    }
}
