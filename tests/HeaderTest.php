<?php

class HeaderTest extends \PHPUnit\Framework\TestCase
{
	/** @test */
	public function can_trim_empty_columns_to_avoid_exceptions_with_ununique_headers() {
		var_dump(\LasseRafn\CsvReader\Reader::make('files/empty-headers.csv')->getRaw());
	}

	/** @test */
	public function will_assign_index_to_empty_headers_where_has_nonempty_rows() {
		echo '';
	}
}
