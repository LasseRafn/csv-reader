<?php

namespace LasseRafn\CsvReader\Tests;

use LasseRafn\CsvReader\Reader;
use PHPUnit\Framework\TestCase;

class ReaderTest extends TestCase
{
    /** @var Reader */
    protected $reader;

    public function testStream()
    {
        $output = [];

        $this->reader->stream(function ($item) use (&$output) {
            $output[$item['id']] = $item['name'];
        });

        $this->assertEquals([
            '1' => 'John',
            '2' => 'Mandy',
            '3' => 'Lasse',
        ], $output);
    }

    public function testGetHeader()
    {
        $this->assertEquals([
            'id',
            'name',
            'is admin',
        ], $this->reader->getHeader());
    }

    public function testGet()
    {
        $this->assertCount(3, $this->reader->get());
        $this->assertEquals([
            ['id' => '1', 'name' => 'John', 'is admin' => 'false'],
            ['id' => '2', 'name' => 'Mandy', 'is admin' => 'false'],
            ['id' => '3', 'name' => 'Lasse', 'is admin' => 'true'],
        ], $this->reader->get());
    }

    public function testPluckValidColumn()
    {
        $this->assertCount(3, $this->reader->pluck('name'));
        $this->assertEquals([
            'John',
            'Mandy',
            'Lasse',
        ], $this->reader->pluck('name'));
    }

    public function testPluckInvalidColumn()
    {
        $this->assertCount(0, $this->reader->pluck('some column that does not exist'));
        $this->assertEquals([], $this->reader->pluck('some column that does not exist'));
    }

    public function testMake()
    {
        $this->reader = Reader::make(__DIR__.'/stubs/valid-semicolon-file.csv');
        $this->assertCount(3, $this->reader->get());
    }

    public function testGetContent()
    {
        $this->assertEquals(file_get_contents(__DIR__.'/stubs/valid-semicolon-file.csv'), $this->reader->getContent());
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->reader = new Reader(__DIR__.'/stubs/valid-semicolon-file.csv');
    }
}
