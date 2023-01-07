<?php namespace LasseRafn\CsvReader;

use LasseRafn\CsvReader\Exceptions\InvalidCSVException;
use League\Csv\CharsetConverter;

class Reader
{
	/** @var \Iterator|\League\Csv\Reader */
	protected $csv;

	protected const DELIMITERS = [ ',', "\t", ';', '|', ':' ];

	protected $_STRIPPING = [
		'non_unique' => '__NON_UNIQUE__:',
		'empty'      => '__EMPTY__:',
	];

	public const SUPPORTED_ENCODINGS = [
		'UTF-8',
		'ASCII',
		'ISO-8859-1',
		'ISO-8859-2',
		'ISO-8859-3',
		'ISO-8859-4',
		'ISO-8859-5',
		'ISO-8859-6',
		'ISO-8859-7',
		'ISO-8859-8',
		'ISO-8859-9',
		'ISO-8859-10',
		'ISO-8859-13',
		'ISO-8859-14',
		'ISO-8859-15',
		'ISO-8859-16',
		'Windows-1251',
		'Windows-1252',
		'Windows-1254',
	];

	public const EXCEL_ENCODINGS = [
		'UTF-8',
		'Windows-1251',
		'Windows-1252',
		'Windows-1254',
	];

	/**
	 * @param $document
	 *
	 * @throws \League\Csv\Exception
	 * @throws InvalidCSVException
	 */
	public function __construct( $document ) {
		if ( ! ini_get( 'auto_detect_line_endings' ) ) {
			ini_set( 'auto_detect_line_endings', '1' );
		}

		if(true) {
			echo 'a';
		} else {
			echo 'c';
		}

		$this->csv = static::initReader( $document );
		$this->setDelimiter( $this->delimiter( $this->csv->getContent() ) );
		$this->setHeaderOffset( 0 );

		$input_bom = $this->csv->getInputBOM();

		if ( $input_bom === \League\Csv\Reader::BOM_UTF16_LE || $input_bom === \League\Csv\Reader::BOM_UTF16_BE ) {
			CharsetConverter::addTo( $this->csv, 'utf-16', 'utf-8' );
		}
	}

	/**
	 * Example: Convert from ISO-8859-1 to UTF-8
	 *
	 * @param        $from
	 * @param string $to
	 *
	 * @return $this
	 */
	public function addCharsetConversion( $from, $to = 'utf-8' ) {
		CharsetConverter::addTo( $this->csv, $from, $to );

		return $this;
	}

	/**
	 * Set the delimiter used to separate columns.
	 *
	 * @param string $delimiter
	 *
	 * @return $this
	 * @throws \League\Csv\Exception
	 */
	public function setDelimiter( $delimiter = ',' ) {
		$this->csv->setDelimiter( $delimiter );

		return $this;
	}

	/**
	 * Automatically encode the content.
	 *
	 * @param string     $to
	 * @param null|array $encodings
	 *
	 * @return $this
	 */
	public function autoEncode( $to = 'utf-8', $encodings = null ) {
		return $this->addCharsetConversion(
			mb_detect_encoding( mb_substr( $this->csv->getContent(), 0, 1024 ), $encodings ?? static::SUPPORTED_ENCODINGS ),
			$to
		);
	}

	/**
	 * Gets an array of the headers / columns of the file.
	 * It strips duplicate and empty headers.
	 *
	 * @return array|string[]
	 */
	public function getHeader(): array {
		$headers     = array_filter( array_map( 'trim', $this->csv->getHeader() ) );
		$usedHeaders = [];

		$headers = array_filter( $headers, function ( $header ) use ( &$usedHeaders ) {
			if ( in_array( $header, $usedHeaders, true ) ) {
				return false;
			}

			$usedHeaders[] = $header;

			return true;
		} );

		return array_values( $headers );
	}

	/**
	 *
	 * @return array|string[]
	 */
	protected function getAllHeaders(): array {
		$trimmedHeader = array_map( 'trim', $this->csv->getHeader() );
		$usedHeaders   = [];

		return array_map( function ( $header, $index ) use ( &$usedHeaders ) {
			// Avoid empty headers
			if ( $header === '' ) {
				return $this->_STRIPPING['empty'] . $index;
			}

			// Avoid duplicates
			if ( in_array( $header, $usedHeaders, true ) ) {
				return $this->_STRIPPING['non_unique'] . $index;
			}
			$usedHeaders[] = $header;

			return $header;
		}, $trimmedHeader, array_keys( $trimmedHeader ) );
	}

	/**
	 * @param int $offset
	 *
	 * @return Reader
	 * @throws \League\Csv\Exception
	 */
	public function setHeaderOffset( $offset = 0 ) {
		$this->csv->setHeaderOffset( $offset );

		return $this;
	}

	/**
	 * @param $document
	 *
	 * @return Reader
	 * @throws \League\Csv\Exception
	 * @throws InvalidCSVException
	 */
	public static function make( $document ) {
		return new static( $document );
	}

	/**
	 * @return array
	 */
	public function get() {
		$items = [];

		foreach ( $this->getIterable() as $item ) {
			$items[] = $this->filterRow( array_map( 'trim', $item ) );
		}

		return $items;
	}

	/**
	 * Get list of unique values from column
	 *
	 * @param string $column
	 *
	 * @return array
	 */
	public function pluck( $column ) {
		// If column does not exist, return empty array
		if ( ! in_array( $column, $this->getHeader(), true ) ) {
			return [];
		}

		$unique = [];

		foreach ( $this->getIterable() as $item ) {
			if ( array_key_exists( $column, $item ) && ! in_array( $item[ $column ], $unique, true ) ) {
				$unique[] = trim( $item[ $column ] ?? null );
			}
		}

		return $unique;
	}

	/**
	 * @param callable $callback
	 */
	public function stream( callable $callback ) {
		foreach ( $this->getIterable() as $item ) {
			$callback( $this->filterRow( array_map( 'trim', $item ) ) );
		}
	}

	/**
	 * @return \Iterator
	 */
	public function getIterable() {
		return $this->csv->getRecords( $this->getAllHeaders() );
	}

	/**
	 * @return \Iterator|\League\Csv\Reader
	 */
	public function getCsv() {
		return $this->csv;
	}

	/**
	 * @return string
	 */
	public function getContent() {
		return (string) $this->csv->getContent();
	}

	/**
	 * @return int
	 */
	public function count() {
		return (int) iterator_count( $this->getIterable() );
	}

	/**
	 * @param $document
	 *
	 * @return \League\Csv\Reader
	 * @throws InvalidCSVException
	 */
	protected static function initReader( $document ) {
		if ( $document === null ) {
			throw new InvalidCSVException( 'No document specified' );
		}

		if ( $document instanceof \SplFileObject || @file_exists( $document ) ) {
			return \League\Csv\Reader::createFromPath( $document );
		}

		if ( \is_resource( $document ) ) {
			return \League\Csv\Reader::createFromStream( $document );
		}

		return \League\Csv\Reader::createFromString( $document );
	}

	/**
	 * @param     $content
	 * @param int $linesToCheck
	 *
	 * @return mixed
	 */
	public static function delimiter( $content, $linesToCheck = 5 ) {
		$results = [];
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

		// Default to first delimiter available
		if ( \count( $results ) === 0 ) {
			return static::DELIMITERS[0];
		}

		$results = array_keys( $results, max( $results ) );

		return $results[0];
	}

	protected function filterRow( $row ) {
		return array_filter( $row, function ( $rowColumnKey ) {
			return strpos( $rowColumnKey, $this->_STRIPPING['empty'] ) === false
			       && strpos( $rowColumnKey, $this->_STRIPPING['non_unique'] ) === false;
		}, ARRAY_FILTER_USE_KEY );
	}
}
