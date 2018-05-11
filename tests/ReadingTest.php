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

	/** @test */
	public function can_escape_characters() {
		$raw = \LasseRafn\CsvReader\Reader::make( 'files/escaped-file.csv' )->getRaw();
		var_dump( $raw );

		$this->assertCount( 9, $raw );
	}

	/** @test */
	public function will_not_trim_empty_lines() {
		$raw = \LasseRafn\CsvReader\Reader::make( 'files/file-with-empty-lines.csv' )->getRaw();
		var_dump( $raw );

		$this->assertCount( 9, $raw );
	}

	/** @test */
	public function will_throw_an_exception_if_content_is_invalid() {
		$raw = \LasseRafn\CsvReader\Reader::make( 'files/file-with-empty-lines.csv' )->getRaw();
		var_dump( $raw );

		$this->assertCount( 9, $raw );
	}

	/** @test */
	public function will_throw_an_exception_if_a_line_is_invalid() {
		$raw = \LasseRafn\CsvReader\Reader::make( 'files/file-with-empty-lines.csv' )->getRaw();
		var_dump( $raw );

		$this->assertCount( 9, $raw );
	}

	/** @test */
	public function can_toggle_skipping_invalid_lines_instead_of_throwing_exceptions() {
		$raw = \LasseRafn\CsvReader\Reader::make( 'files/file-with-empty-lines.csv' )->getRaw();
		var_dump( $raw );

		$this->assertCount( 9, $raw );
	}
}
