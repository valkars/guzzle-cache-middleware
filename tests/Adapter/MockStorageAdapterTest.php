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

use Csa\GuzzleHttp\Middleware\Cache\Adapter\MockStorageAdapter;
use Csa\GuzzleHttp\Middleware\Cache\NamingStrategy\NamingStrategyInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Filesystem\Filesystem;

class MockStorageAdapterTest extends TestCase
{
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var string
     */
    private $tmpDir;

    protected $class = MockStorageAdapter::class;

    protected function setUp(): void
    {
        $this->fs = new Filesystem();

        $this->tmpDir = \sys_get_temp_dir().'/csa_guzzle_bundle_'.\uniqid('', true);
        $this->fs->mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        $this->fs->remove($this->tmpDir);
    }

    public function testFetch()
    {
        /* Mock with host in the file name look for the file with hostname first */

        $mockStorage = new $this->class(__DIR__ . '/../Fixtures/mocks');
        $response = $mockStorage->fetch($this->getRequestMock());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(302, $response->getStatusCode());

        /* Mock with no host in the file name gets the right return code */

        $mockStorage = new $this->class(__DIR__ . '/../Fixtures/mocks');
        $response = $mockStorage->fetch($this->getRequestMockForMockWithNoHost());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(301, $response->getStatusCode());
    }

    public function testSave()
    {
        $request = $this->getRequestMock();
        $mockStorage = new $this->class($this->tmpDir, [], ['X-Foo']);
        $mockStorage->save($request, new Response(404, ['X-Foo' => 'bar'], 'Not found'));
        $response = $mockStorage->fetch($request);

        $this->assertCount(1, glob($this->tmpDir.'/google.com/GET_*'));
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(404, $response->getStatusCode());

        $this->assertFalse($response->hasHeader('X-Foo'));
    }

    public function testSaveWithSubResource()
    {
        $request = new Request(
            'POST',
            'http://api.github.com/user/repos',
            ['Accept' => 'application/vnd.github+json']
        );

        $mockStorage = new $this->class($this->tmpDir, [], ['X-Foo']);
        $mockStorage->save($request, new Response(404, ['X-Foo' => 'bar'], 'Not found'));
        $response = $mockStorage->fetch($request);

        $this->assertCount(1, glob($this->tmpDir.'/api.github.com/user/repos/POST_*'));
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(404, $response->getStatusCode());

        $this->assertFalse($response->hasHeader('X-Foo'));
    }

    public function testFetchWithInjectedNamingStrategy()
    {
        $namingStrategy = $this->createMock(NamingStrategyInterface::class);
        $request = $this->getRequestMock();
        $adapter = new $this->class($this->tmpDir, [], [], $namingStrategy);

        $namingStrategy->expects($this->once())->method('filename')->with($request);

        $adapter->fetch($request);
    }

    public function testSaveWithInjectedNamingStrategy()
    {
        $namingStrategy = $this->createMock(NamingStrategyInterface::class);
        $request = $this->getRequestMock();
        $adapter = new $this->class($this->tmpDir, [], [], $namingStrategy);

        $namingStrategy->expects($this->once())->method('filename')->with($request);

        $adapter->save($request, new Response());
    }

    private function getRequestMock()
    {
        return new Request('GET', 'http://google.com/', ['Accept' => 'text/html']);
    }

    private function getRequestMockForMockWithNoHost()
    {
        return new Request('GET', 'http://yahoo.com/', ['Accept' => 'text/html']);
    }
}
