<?php namespace LasseRafn\CsvReader;

use LasseRafn\CsvReader\Exceptions\InvalidCSVException;

class Parser
{
	protected const DELIMITERS = [ ',', '\t', ';', '|', ':' ];

	/** @var string|\SplFileObject */
	protected $content   = '';
	protected $delimiter = ',';
	protected $enclosure = '"';
	protected $escape    = '\\';

	/**
	 * Parser constructor.
	 *
	 * @param string|\SplFileObject $content
	 * @param null                  $delimiter
	 * @param string                $enclosure
	 * @param string                $escape
	 */
	public function __construct( $content, $delimiter = null, $enclosure = '"', $escape = '\\' ) {
		$this->escape    = $escape;
		$this->content   = $content;
		$this->enclosure = $enclosure;
		$this->delimiter = $delimiter ?? static::delimiter( $this->content );
	}

	/**
	 * @return array
	 * @throws InvalidCSVException
	 */
	public function read() {
		if ( $this->content instanceof \SplFileObject ) {
			return static::validate( $this->readFileObject() );
		}

		if ( file_exists( $this->content ) ) {
			return static::validate( $this->readFile() );
		}

		if ( \is_string( $this->content ) ) {
			return static::validate( str_getcsv( $this->content, $this->delimiter, $this->enclosure, $this->escape ) );
		}

		throw new InvalidCSVException( 'The supplied content is not valid.' );
	}

	protected function readFileObject() {
		$data = [];

		while ( ! $this->content->eof() ) {
			$data[] = $this->content->fgetcsv( $this->delimiter, $this->enclosure, $this->escape );
		}

		return $data;
	}

	protected function readFile() {
		$handle = fopen( $this->content, 'rb' );
		$data   = [];

		while ( $row = fgetcsv( $handle, 0, $this->delimiter, $this->enclosure, $this->escape ) ) {
			$data[] = $row;
		}

		return $data;
	}


	protected static function getRaw( $content ) {
		if ( $content instanceof \SplFileObject ) {
			return $content->fread( $content->getSize() );
		}

		if ( file_exists( $content ) ) {
			return fgets( fopen( $content, 'rb' ) );
		}

		return $content;
	}

	/**
	 * @param $csv
	 *
	 * @return array
	 * @throws InvalidCSVException
	 */
	protected static function validate( $csv ) {
		if ( ! $csv ) {
			throw new InvalidCSVException( 'The supplied content is not valid.' );
		}

		return $csv;
	}

	/**
	 * Guess the delimiter used.
	 *
	 * @param     $content
	 * @param int $linesToCheck
	 *
	 * @return mixed
	 */
	protected static function delimiter( $content, $linesToCheck = 5 ) {
		$results = [];
		$content = static::getRaw( $content );
		$lines   = preg_split( "/((\r?\n)|(\r\n?))/", $content );

		$linesToCheck = min( \count( $lines ), $linesToCheck );

		for ( $i = 0; $i < $linesToCheck; $i++ ) {
			foreach ( static::DELIMITERS as $delimiter ) {
				$regExp = '/[' . $delimiter . ']/';
				$fields = preg_split( $regExp, $lines[ $i ] );

				if ( \count( $fields ) > 1 ) {
					if ( ! empty( $results[ $delimiter ] ) ) {
						$results[ $delimiter ]++;
					} else {
						$results[ $delimiter ] = 1;
					}
				}
			}
			$i++;
		}

		$results = array_keys( $results, max( $results ) );

		return $results[0];
	}
}