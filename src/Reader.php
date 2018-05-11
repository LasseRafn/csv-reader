<?php namespace LasseRafn\CsvReader;

class Reader
{
	protected $parser;
	protected $raw;

	/**
	 * $content can be raw csv, an \SplFileObject
	 * or an absolute path to the file.
	 *
	 * @param string|\SplFileObject $content
	 * @param null|string           $delimiter
	 * @param string $enclosure
	 * @param string $escape
	 *
	 * @throws Exceptions\InvalidCSVException
	 */
	public function __construct( $content, $delimiter = null, $enclosure = "\"", $escape = "\\" ) {
		$this->parser = new Parser( $content, $delimiter, $enclosure, $escape );
		$this->raw = $this->parser->read();
	}


	/**
	 * $content can be raw csv, an \SplFileObject
	 * or an absolute path to the file.
	 *
	 * @param string|\SplFileObject $content
	 * @param null|string           $delimiter
	 * @param string $enclosure
	 * @param string $escape
	 *
	 * @return self
	 *
	 * @throws Exceptions\InvalidCSVException
	 */
	public static function make( $content, $delimiter = null, $enclosure = "\"", $escape = "\\" ) {
		return new self( $content, $delimiter, $enclosure, $escape );
	}

	public function getRaw() {
		return $this->raw;
	}
}