<?php

namespace Csa\GuzzleHttp\Middleware\Cache\Adapter;

use Csa\GuzzleHttp\Middleware\Cache\NamingStrategy\LegacyNamingStrategy;
use Csa\GuzzleHttp\Middleware\Cache\NamingStrategy\NamingStrategyInterface;
use Csa\GuzzleHttp\Middleware\Cache\NamingStrategy\SubfolderNamingStrategy;
use Csa\GuzzleHttp\Middleware\Cache\CacheMiddleware;
use Csa\GuzzleHttp\Middleware\Cache\MockMiddleware;
use GuzzleHttp\Psr7\Message;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Filesystem\Filesystem;

class MockStorageAdapter implements StorageAdapterInterface
{
    /** @var NamingStrategyInterface[] */
    private array $namingStrategies = [];
    private array $responseHeadersBlacklist = [
        CacheMiddleware::DEBUG_HEADER,
        MockMiddleware::DEBUG_HEADER,
    ];

    public function __construct(private readonly string $storagePath, array $requestHeadersBlacklist = [], array $responseHeadersBlacklist = [], ?NamingStrategyInterface $namingStrategy = null)
    {
        if ($namingStrategy) {
            $this->namingStrategies[] = $namingStrategy;
        } else {
            $this->namingStrategies[] = new SubfolderNamingStrategy($requestHeadersBlacklist);
            $this->namingStrategies[] = new LegacyNamingStrategy(true, $requestHeadersBlacklist);
            $this->namingStrategies[] = new LegacyNamingStrategy(false, $requestHeadersBlacklist);
        }

        if (!empty($responseHeadersBlacklist)) {
            $this->responseHeadersBlacklist = $responseHeadersBlacklist;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(RequestInterface $request): ?ResponseInterface
    {
        foreach ($this->namingStrategies as $strategy) {
            if (\file_exists($filename = $this->getFilename($strategy->filename($request)))) {
                return Message::parseResponse(\file_get_contents($filename));
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function save(RequestInterface $request, ResponseInterface $response): void
    {
        foreach ($this->responseHeadersBlacklist as $header) {
            $response = $response->withoutHeader($header);
        }

        [$strategy] = $this->namingStrategies;

        $filename = $this->getFilename($strategy->filename($request));

        $fs = new Filesystem();
        $fs->mkdir(\dirname($filename));

        \file_put_contents($filename, Message::toString($response));
        $response->getBody()->rewind();
    }

    /**
     * Prefixes the generated file path with the adapter's storage path.
     *
     * @return string The path to the mock file
     */
    private function getFilename(?string $name): string
    {
        return $this->storagePath.'/'.$name.'.txt';
    }
}
