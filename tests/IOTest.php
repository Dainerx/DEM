<?php
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use DEM\IO;

class IOTest extends TestCase
{
    const TEST_GENERATED_FILES = "test_generated_files";
    /**
     * @var  vfsStreamDirectory
     */
    private $root;

    public function testFileIsCreated()
    {
        $this->root = vfsStream::setup(self::TEST_GENERATED_FILES);
        $filename = 'hello.txt';
        $content = 'Hello world';
        $this->assertFalse($this->root->hasChild($filename));
        IO::writeFile(vfsStream::url(self::TEST_GENERATED_FILES . '/' . $filename), $content);
        $this->assertTrue($this->root->hasChild($filename));
    }

    public function testFileIsDeleted()
    {
        $this->root = vfsStream::setup(self::TEST_GENERATED_FILES);
        $filename = 'hello.txt';
        $content = 'Hello world';
        IO::writeFile(vfsStream::url(self::TEST_GENERATED_FILES . '/' . $filename), $content);
        $this->assertTrue($this->root->hasChild($filename));
        IO::removeFile(vfsStream::url(self::TEST_GENERATED_FILES . '/' . $filename));
        $this->assertFalse($this->root->hasChild($filename));
    }

    public function testReadingContent()
    {
        $this->root = vfsStream::setup(self::TEST_GENERATED_FILES);
        $filename = 'test.txt';
        $contentToTestAgainst = 'some content to test against';
        IO::writeFile(vfsStream::url(self::TEST_GENERATED_FILES . '/' . $filename), $contentToTestAgainst);
        $content = IO::readFile(vfsStream::url(self::TEST_GENERATED_FILES . '/' . $filename));
        $this->assertEquals($contentToTestAgainst, $content);
    }
}
