<?php
/**
 * nFrastructure (https://nfrastructure.com/)
 *
 * @link      https://github.com/nfrastructuredev/php-docker-compose for the canonical source repository
 * @copyright Copyright (c) 2018 nFrastructure (https://nfrastructure.com/)
 * @license   https://github.com/nfrastructuredev/php-docker-compose/license MIT
 */


namespace NfrastructureTest\ComposerTest;

use Composer\Script\Event;
use Nfrastructure\Composer\CreateCompose;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;

/**
 * Ensures that CreateCompose behaves as expected
 */
class CreateComposeTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Event|\Mockery\MockInterface
     */
    protected $composerEvent;

    /**
     * @var vfsStreamDirectory
     */
    protected $root;

    /**
     * @before
     */
    public function setUpEvent()
    {
        $this->composerEvent = \Mockery::mock(Event::class);
    }

    /**
     * @before
     */
    public function setupFileSystem()
    {
        $this->root = vfsStream::setup("root", null);
    }

    /**
     * @test
     */
    public function testItShouldFailWhenExtrasEmpty()
    {
        $this->expectException(
            \InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'The docker-compose needs to be configured through the extra.docker-compose setting.'
        );

        $this->composerEvent->shouldReceive('getComposer->getPackage->getExtra')
            ->andReturn([]);

        CreateCompose::create($this->composerEvent);
    }

    /**
     * @test
     */
    public function testItShouldFailWhenExtrasMissingKey()
    {
        $this->expectException(
            \InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'The docker-compose needs to be configured through the extra.docker-compose setting.'
        );

        $this->composerEvent->shouldReceive('getComposer->getPackage->getExtra')
            ->andReturn(['foo' => 'bar']);

        CreateCompose::create($this->composerEvent);
    }

    /**
     * @test
     */
    public function testItShouldFailWhenExtrasIsNotHash()
    {
        $this->expectException(
            \InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'The extra.docker-compose must be YMAL like.'
        );

        $this->composerEvent->shouldReceive('getComposer->getPackage->getExtra')
            ->andReturn(['docker-compose' => range(1, 10)]);

        CreateCompose::create($this->composerEvent);
    }

    /**
     * @test
     */
    public function testItShouldReplaceValues()
    {
        $this->composerEvent->shouldReceive('getComposer->getPackage->getExtra')
            ->andReturn(['docker-compose' => $this->getValidExtras()]);

        CreateCompose::create($this->composerEvent);

        $this->assertTrue(
            $this->root->hasChild('docker-compose.yml')
        );

        $this->assertEquals(
            file_get_contents(__DIR__ . '/files/docker-compose.yml'),
            $this->root->getChild('docker-compose.yml')->getContent()
        );
    }

    /**
     * @test
     */
    public function testItShouldReplaceValuesWithNoMatch()
    {
        $extras = $this->getValidExtras();
        $extras['compose']['services']['php']['environment']['FOO'] = '{{ BAR }}';
        $this->composerEvent->shouldReceive('getComposer->getPackage->getExtra')
            ->andReturn(['docker-compose' => $extras]);

        CreateCompose::create($this->composerEvent);

        $this->assertTrue(
            $this->root->hasChild('docker-compose.yml')
        );

        $this->assertEquals(
            file_get_contents(__DIR__ . '/files/docker-compose-no-match.yml'),
            $this->root->getChild('docker-compose.yml')->getContent()
        );
    }

    protected function getValidExtras()
    {
        return [
            'compose-file' => vfsStream::url('root/docker-compose.yml'),
            'compose' => [
                'version' => '3',
                'services' => [
                    'php' => [
                        'container_name' => 'my_php',
                        'image' => 'php',
                        'expose' => [
                            0 => '9001',
                        ],
                        'environment' => [
                            'PHP_XDEBUG_ENABLED' => 1,
                            'XDEBUG_CONFIG' => 'remote_enable=1 remote_mode=req remote_port=9001 ' .
                                'remote_connect_back=0 remote_host={{ HOST_IP }}',
                        ],
                        'volumes' => [
                            0 => './:/var/www',
                        ],
                    ],
                ],
            ],
        ];
    }
}
