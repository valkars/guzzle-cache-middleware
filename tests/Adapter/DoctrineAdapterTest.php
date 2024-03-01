<?php

/*
 * This file is part of the CsaGuzzleBundle package
 *
 * (c) Charles Sarrazin <charles@sarraz.in>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace Csa\Tests\GuzzleHttp\Middleware\Cache\Adapter;

use Csa\GuzzleHttp\Middleware\Cache\Adapter\DoctrineAdapter;
use Csa\GuzzleHttp\Middleware\Cache\NamingStrategy\NamingStrategyInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Doctrine\Common\Cache\Cache;

class DoctrineAdapterTest extends TestCase
{
    protected $class = DoctrineAdapter::class;

    public function testFetchWithInjectedNamingStrategy(): void
    {
        $cache = $this->createMock(Cache::class);
        $namingStrategy = $this->createMock(NamingStrategyInterface::class);
        $request = $this->getRequestMock();
        $adapter = new $this->class($cache, 0, $namingStrategy);

        $namingStrategy->expects($this->once())->method('filename')->with($request);

        $adapter->fetch($request);
    }

    public function testSaveWithInjectedNamingStrategy(): void
    {
        $cache = $this->createMock(Cache::class);
        $namingStrategy = $this->createMock(NamingStrategyInterface::class);
        $request = $this->getRequestMock();
        $response = $this->getResponseMock();
        $adapter = new $this->class($cache, 0, $namingStrategy);

        $namingStrategy->expects($this->once())->method('filename')->with($request);

        $adapter->save($request, $response);
    }

    private function getRequestMock(): Request
    {
        return new Request('GET', 'https://google.com/', ['Accept' => 'text/html']);
    }

    private function getResponseMock(): Response
    {
        return new Response(200, [], 'Hello World');
    }
}
