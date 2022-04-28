<?php

/*
 * This file is part of the CsaGuzzleBundle package
 *
 * (c) Charles Sarrazin <charles@sarraz.in>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace Csa\Tests\GuzzleHttp\Middleware\Cache;

use Csa\GuzzleHttp\Middleware\Cache\Adapter\StorageAdapterInterface;
use Csa\GuzzleHttp\Middleware\Cache\MockMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class MockMiddlewareTest extends TestCase
{
    public function testRecord(): void
    {
        $response = new Response(204);
        $mock = new MockHandler([$response]);
        $handler = HandlerStack::create($mock);

        $adapter = $this->createMock(StorageAdapterInterface::class);
        $adapter
            ->expects($this->once())
            ->method('save')
            ->with(
                $this->isInstanceOf(RequestInterface::class),
                $this->equalTo($response)
            )
        ;

        $handler->push(new MockMiddleware($adapter, 'record'));

        $client = new Client(['handler' => $handler]);

        $client->get('https://foo.bar');
    }

    public function testReplay(): void
    {
        $response = new Response(204);
        $mock = new MockHandler([$response]);
        $handler = HandlerStack::create($mock);

        $adapter = $this->createMock(StorageAdapterInterface::class);
        $adapter
            ->expects($this->once())
            ->method('fetch')
            ->with($this->isInstanceOf(RequestInterface::class))
            ->willReturn($response)
        ;

        $handler->push(new MockMiddleware($adapter, 'replay'));

        $client = new Client(['handler' => $handler]);

        $client->get('https://foo.bar');
    }

    public function testReplayFailsWithoutMock(): void
    {
        $this->expectExceptionMessage("Record not found for request: GET https://foo.bar");
        $this->expectException(\RuntimeException::class);
        $handler = HandlerStack::create();

        $adapter = $this->createMock(StorageAdapterInterface::class);
        $adapter
            ->expects($this->once())
            ->method('fetch')
            ->with($this->isInstanceOf(RequestInterface::class))
        ;

        $handler->push(new MockMiddleware($adapter, 'replay'));

        $client = new Client(['handler' => $handler]);

        $client->get('https://foo.bar');
    }
}
