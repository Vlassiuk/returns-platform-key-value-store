<?php

/*
 * This file is part of the webmozart/key-value-store package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\KeyValueStore\Tests;

use Symfony\Component\Filesystem\Filesystem;
use Webmozart\KeyValueStore\JsonFileStore;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class JsonFileStoreTest extends AbstractSortableCountableStoreTest
{
    private $tempDir;

    protected function setUp()
    {
        while (false === @mkdir($this->tempDir = sys_get_temp_dir().'/webmozart-JsonFileStoreTest'.rand(10000, 99999), 0777, true)) {
        }

        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();

        $filesystem = new Filesystem();

        // Ensure all files in the directory are writable before removing
        $filesystem->chmod($this->tempDir, 0755, 0000, true);
        $filesystem->remove($this->tempDir);
    }

    protected function createStore()
    {
        return new JsonFileStore($this->tempDir.'/data.json');
    }

    public function provideScalarValues()
    {
        $values = parent::provideScalarValues();
        $values[] = array(JsonFileStore::MAX_FLOAT);

        return $values;
    }

    public function testCreateMissingDirectoriesOnDemand()
    {
        $store = new JsonFileStore($this->tempDir.'/new/data.json');
        $store->set('foo', 'bar');

        $this->assertFileExists($this->tempDir.'/new/data.json');
    }

    /**
     * @dataProvider provideScalarValues
     */
    public function testSetSupportsScalarValues($value)
    {
        if (is_float($value) && $value > JsonFileStore::MAX_FLOAT) {
            $this->setExpectedException('\Webmozart\KeyValueStore\Api\UnsupportedValueException');
        }

        parent::testSetSupportsScalarValues($value);
    }

    /**
     * @dataProvider provideBinaryValues
     */
    public function testSetSupportsBinaryValues($value)
    {
        // JSON cannot handle binary data
        $this->setExpectedException('\Webmozart\KeyValueStore\Api\UnsupportedValueException');

        parent::testSetSupportsBinaryValues($value);
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\WriteException
     * @expectedExceptionMessage Permission denied
     */
    public function testSetThrowsWriteExceptionIfWriteFails()
    {
        touch($readOnlyFile = $this->tempDir.'/read-only.json');
        $store = new JsonFileStore($readOnlyFile);

        chmod($readOnlyFile, 0400);
        $store->set('foo', 'bar');
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\WriteException
     * @expectedExceptionMessage Permission denied
     */
    public function testRemoveThrowsWriteExceptionIfWriteFails()
    {
        touch($readOnlyFile = $this->tempDir.'/read-only.json');
        $store = new JsonFileStore($readOnlyFile);
        $store->set('foo', 'bar');

        chmod($readOnlyFile, 0400);
        $store->remove('foo');
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\WriteException
     * @expectedExceptionMessage Permission denied
     */
    public function testClearThrowsWriteExceptionIfWriteFails()
    {
        touch($readOnlyFile = $this->tempDir.'/read-only.json');
        $store = new JsonFileStore($readOnlyFile);

        chmod($readOnlyFile, 0400);
        $store->clear();
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\ReadException
     * @expectedExceptionMessage Permission denied
     */
    public function testGetThrowsReadExceptionIfReadFails()
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('Cannot deny read access on Windows.');
        }

        touch($notReadable = $this->tempDir.'/not-readable.json');
        $store = new JsonFileStore($notReadable);

        chmod($notReadable, 0000);
        $store->get('key');
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\ReadException
     * @expectedExceptionMessage JSON_ERROR_SYNTAX
     */
    public function testGetThrowsReadExceptionIfInvalidJsonSyntax()
    {
        file_put_contents($invalid = $this->tempDir.'/data.json', '{"foo":');
        $store = new JsonFileStore($invalid);
        $store->get('key');
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\UnserializationFailedException
     */
    public function testGetThrowsExceptionIfNotUnserializable()
    {
        file_put_contents($path = $this->tempDir.'/data.json', '{"key":"foobar"}');
        $store = new JsonFileStore($path);
        $store->get('key');
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\ReadException
     * @expectedExceptionMessage Permission denied
     */
    public function testGetOrFailThrowsReadExceptionIfReadFails()
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('Cannot deny read access on Windows.');
        }

        touch($notReadable = $this->tempDir.'/not-readable.json');
        $store = new JsonFileStore($notReadable);

        chmod($notReadable, 0000);
        $store->getOrFail('key');
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\ReadException
     * @expectedExceptionMessage JSON_ERROR_SYNTAX
     */
    public function testGetOrFailThrowsReadExceptionIfInvalidJsonSyntax()
    {
        file_put_contents($invalid = $this->tempDir.'/data.json', '{"foo":');
        $store = new JsonFileStore($invalid);
        $store->getOrFail('key');
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\UnserializationFailedException
     */
    public function testGetOrFailThrowsExceptionIfNotUnserializable()
    {
        file_put_contents($path = $this->tempDir.'/data.json', '{"key":"foobar"}');
        $store = new JsonFileStore($path);
        $store->getOrFail('key');
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\ReadException
     * @expectedExceptionMessage Permission denied
     */
    public function testGetMultipleThrowsReadExceptionIfReadFails()
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('Cannot deny read access on Windows.');
        }

        touch($notReadable = $this->tempDir.'/not-readable.json');
        $store = new JsonFileStore($notReadable);

        chmod($notReadable, 0000);
        $store->getMultiple(array('key'));
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\ReadException
     * @expectedExceptionMessage JSON_ERROR_SYNTAX
     */
    public function testGetMultipleThrowsReadExceptionIfInvalidJsonSyntax()
    {
        file_put_contents($invalid = $this->tempDir.'/data.json', '{"foo":');
        $store = new JsonFileStore($invalid);
        $store->getMultiple(array('key'));
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\UnserializationFailedException
     */
    public function testGetMultipleThrowsExceptionIfNotUnserializable()
    {
        file_put_contents($path = $this->tempDir.'/data.json', '{"key":"foobar"}');
        $store = new JsonFileStore($path);
        $store->getMultiple(array('key'));
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\ReadException
     * @expectedExceptionMessage Permission denied
     */
    public function testGetMultipleOrFailThrowsReadExceptionIfReadFails()
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('Cannot deny read access on Windows.');
        }

        touch($notReadable = $this->tempDir.'/not-readable.json');
        $store = new JsonFileStore($notReadable);

        chmod($notReadable, 0000);
        $store->getMultipleOrFail(array('key'));
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\ReadException
     * @expectedExceptionMessage JSON_ERROR_SYNTAX
     */
    public function testGetMultipleOrFailThrowsReadExceptionIfInvalidJsonSyntax()
    {
        file_put_contents($invalid = $this->tempDir.'/data.json', '{"foo":');
        $store = new JsonFileStore($invalid);
        $store->getMultipleOrFail(array('key'));
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\UnserializationFailedException
     */
    public function testGetMultipleOrFailThrowsExceptionIfNotUnserializable()
    {
        file_put_contents($path = $this->tempDir.'/data.json', '{"key":"foobar"}');
        $store = new JsonFileStore($path);
        $store->getMultipleOrFail(array('key'));
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\ReadException
     * @expectedExceptionMessage Permission denied
     */
    public function testExistsThrowsReadExceptionIfReadFails()
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('Cannot deny read access on Windows.');
        }

        touch($notReadable = $this->tempDir.'/not-readable.json');
        $store = new JsonFileStore($notReadable);

        chmod($notReadable, 0000);
        $store->exists('key');
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\ReadException
     * @expectedExceptionMessage Permission denied
     */
    public function testKeysThrowsReadExceptionIfReadFails()
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('Cannot deny read access on Windows.');
        }

        touch($notReadable = $this->tempDir.'/not-readable.json');
        $store = new JsonFileStore($notReadable);

        chmod($notReadable, 0000);
        $store->keys();
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\ReadException
     */
    public function testSortThrowsReadExceptionIfReadFails()
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('Cannot deny read access on Windows.');
        }

        touch($notReadable = $this->tempDir.'/not-readable.json');
        $store = new JsonFileStore($notReadable);

        chmod($notReadable, 0000);
        $store->sort();
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\WriteException
     */
    public function testSortThrowsWriteExceptionIfWriteFails()
    {
        touch($readOnlyFile = $this->tempDir.'/read-only.json');
        $store = new JsonFileStore($readOnlyFile);

        chmod($readOnlyFile, 0400);
        $store->sort();
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\ReadException
     */
    public function testCountThrowsReadExceptionIfReadFails()
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('Cannot deny read access on Windows.');
        }

        touch($notReadable = $this->tempDir.'/not-readable.json');
        $store = new JsonFileStore($notReadable);

        chmod($notReadable, 0000);
        $store->count();
    }
}
