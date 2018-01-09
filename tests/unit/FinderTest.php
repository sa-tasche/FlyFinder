<?php
/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2010-2015 Mike van Riel<mike@phpdoc.org>
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://phpdoc.org
 */

namespace Flyfinder;

use Flyfinder\Specification\IsHidden;
use League\Flysystem\Filesystem;
use Mockery as m;
use PHPUnit\Framework\TestCase;

/**
 * Test case for Finder
 * @coversDefaultClass Flyfinder\Finder
 */
class FinderTest extends TestCase
{
    /** @var Finder */
    private $fixture;

    /**
     * Initializes the fixture for this test.
     */
    public function setUp()
    {
        $this->fixture = new Finder();
    }

    public function tearDown()
    {
        m::close();
    }

    /**
     * @covers ::getMethod
     */
    public function testGetMethod()
    {
        $this->assertSame('find', $this->fixture->getMethod());
    }

    /**
     * @covers ::handle
     * @covers ::setFilesystem
     * @covers ::<private>
     */
    public function testIfCorrectFilesAreBeingYielded()
    {
        $isHidden = m::mock(IsHidden::class);
        $filesystem = m::mock(Filesystem::class);

        $listContents1 = [
            0 => [
                'type' => 'dir',
                'path' => '.hiddendir',
                'dirname' => '',
                'basename' => '.hiddendir',
                'filename' => '.hiddendir',
            ],
            1 => [
                'type' => 'file',
                'path' => 'test.txt',
                'basename' => 'test.txt',
            ],
        ];

        $listContents2 = [
            0 => [
                'type' => 'file',
                'path' => '.hiddendir/.test.txt',
                'dirname' => '.hiddendir',
                'basename' => '.test.txt',
                'filename' => '.test',
                'extension' => 'txt',
            ],
        ];

        $filesystem->shouldReceive('listContents')
            ->with('')
            ->andReturn($listContents1);

        $filesystem->shouldReceive('listContents')
            ->with('.hiddendir')
            ->andReturn($listContents2);

        $isHidden->shouldReceive('isSatisfiedBy')
            ->with($listContents1[0])
            ->andReturn(true);

        $isHidden->shouldReceive('isSatisfiedBy')
            ->with($listContents1[1])
            ->andReturn(false);

        $isHidden->shouldReceive('isSatisfiedBy')
            ->with($listContents2[0])
            ->andReturn(true);

        $this->fixture->setFilesystem($filesystem);
        $generator = $this->fixture->handle($isHidden);

        $result = [];

        foreach ($generator as $value) {
            $result[] = $value;
        }

        $expected = [
            0 => [
                'type' => 'file',
                'path' => '.hiddendir/.test.txt',
                'dirname' => '.hiddendir',
                'basename' => '.test.txt',
                'filename' => '.test',
                'extension' => 'txt',
            ],
        ];

        $this->assertSame($expected, $result);
    }
}
