<?php

namespace LasseRafn\CsvReader\Tests;

use LasseRafn\CsvReader\Reader;
use PHPUnit\Framework\TestCase;

class DuplicateHeaderTest extends TestCase
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
            '2' => 'Francis',
        ], $output);
    }

    public function testGetHeader()
    {
        $this->assertEquals([
            'id',
            'name',
            'is_admin',
        ], $this->reader->getHeader());
        //
    }

    public function testGet()
    {
        $this->assertCount(2, $this->reader->get());
        $this->assertEquals(
            [
                ['id' => '1', 'name' => 'John', 'is_admin' => '1'],
                ['id' => '2', 'name' => 'Francis', 'is_admin' => '1'],
            ],
            $this->reader->get()
        );
    }

    public function testPluckValidColumn()
    {
        $this->assertCount(2, $this->reader->pluck('name'));
        $this->assertEquals([
            'John',
            'Francis',
        ], $this->reader->pluck('name'));
    }

    public function testPluckInvalidColumn()
    {
        $this->assertCount(0, $this->reader->pluck('some column that does not exist'));
        $this->assertEquals([], $this->reader->pluck('some column that does not exist'));
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->reader = new Reader(__DIR__.'/stubs/duplicate-column-file.csv');
    }
}
