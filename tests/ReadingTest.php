<?php

class ReadingTest extends \PHPUnit\Framework\TestCase
{
	/** @test */
	public function can_read_a_valid_file() {
		$raw = \LasseRafn\CsvReader\Reader::make( 'files/regular-file.csv' )->getRaw();

		$this->assertCount( 9, $raw );
	}

	/** @test */
	public function can_read_from_a_string() {
		$raw = \LasseRafn\CsvReader\Reader::make( "id;name\n1;John\n2;Doe" )->getRaw();

		$this->assertCount( 3, $raw );
	}

	/** @test */
	public function can_read_a_spl_file_object() {
		$raw = \LasseRafn\CsvReader\Reader::make( new SplFileObject( 'files/regular-file.csv', 'rb+' ) )->getRaw();

		$this->assertCount( 9, $raw );
	}
}
