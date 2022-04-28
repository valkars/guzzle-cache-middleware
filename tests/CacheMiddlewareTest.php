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
use Csa\GuzzleHttp\Middleware\Cache\CacheMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class CacheMiddlewareTest extends TestCase
{
    public function testMiddleware()
    {
        $response = new Response(204);
        $mocks = array_fill(0, 2, $response);
        $mock = new MockHandler($mocks);
        $handler = HandlerStack::create($mock);

        $adapter = $this->createMock(StorageAdapterInterface::class);
        $adapter
            ->expects($this->at(0))
            ->method('fetch')
            ->with($this->isInstanceOf(RequestInterface::class))
        ;
        $adapter
            ->expects($this->at(1))
            ->method('save')
            ->with(
                $this->isInstanceOf(RequestInterface::class),
                $this->equalTo($response)
            )
        ;
        $adapter
            ->expects($this->at(2))
            ->method('fetch')
            ->with($this->isInstanceOf(RequestInterface::class))
            ->willReturn($response)
        ;

        $handler->push(new CacheMiddleware($adapter));

        $client = new Client(['handler' => $handler]);

        $client->get('https://foo.bar');

        $client->get('https://foo.bar');
    }
}
