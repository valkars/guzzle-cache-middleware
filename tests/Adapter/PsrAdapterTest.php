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

use Csa\GuzzleHttp\Middleware\Cache\Adapter\PsrAdapter;
use Csa\GuzzleHttp\Middleware\Cache\NamingStrategy\NamingStrategyInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;

class PsrAdapterTest extends TestCase
{
    public function testFetch(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $item = $this->createMock(CacheItemInterface::class);

        $item
            ->expects($this->at(0))
            ->method('isHit')
            ->willReturn(false)
        ;
        $item
            ->expects($this->at(1))
            ->method('isHit')
            ->willReturn(true)
        ;
        $item
            ->expects($this->at(2))
            ->method('get')
            ->willReturn([
                'status' => 200,
                'headers' => [],
                'body' => 'Hello World',
                'version' => '1.1',
                'reason' => 'OK',
            ])
        ;
        $cache
            ->expects($this->exactly(2))
            ->method('getItem')
            ->willReturn($item)
        ;
        $adapter = new PsrAdapter($cache, 0);

        $request = $this->getRequestMock();

        $this->assertNull($adapter->fetch($request));
        $this->assertInstanceOf(ResponseInterface::class, $adapter->fetch($request));
    }

    public function testSave(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $item = $this->createMock(CacheItemInterface::class);

        $item
            ->expects($this->at(0))
            ->method('expiresAfter')
            ->with(10)
        ;
        $item
            ->expects($this->at(1))
            ->method('set')
            ->with([
                'status' => 200,
                'headers' => [],
                'body' => 'Hello World',
                'version' => '1.1',
                'reason' => 'OK',
            ])
        ;
        $cache
            ->expects($this->at(0))
            ->method('getItem')
            ->willReturn($item)
        ;
        $cache
            ->expects($this->at(1))
            ->method('save')
            ->with($item)
        ;
        $adapter = new PsrAdapter($cache, 10);
        $adapter->save($this->getRequestMock(), $this->getResponseMock());
    }

    public function testFetchWithInjectedNamingStrategy(): void
    {
        $cache = $this->getCacheMock();
        $namingStrategy = $this->createMock(NamingStrategyInterface::class);
        $request = $this->getRequestMock();
        $adapter = new PsrAdapter($cache, 0, $namingStrategy);

        $namingStrategy->expects($this->once())->method('filename')->with($request);

        $adapter->fetch($request);
    }

    public function testSaveWithInjectedNamingStrategy(): void
    {
        $cache = $this->getCacheMock();
        $namingStrategy = $this->createMock(NamingStrategyInterface::class);
        $request = $this->getRequestMock();
        $response = $this->getResponseMock();
        $adapter = new PsrAdapter($cache, 0, $namingStrategy);

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

    private function getCacheMock(): MockObject|CacheItemPoolInterface
    {
        $item = $this->createMock(CacheItemInterface::class);
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->method('getItem')->willReturn($item);

        return $cache;
    }
}
