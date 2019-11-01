<?php namespace LasseRafn\CsvReader;

use LasseRafn\CsvReader\Exceptions\InvalidCSVException;
use League\Csv\CharsetConverter;

class Reader
{
	/** @var \Iterator|\League\Csv\Reader */
	protected $csv;

	protected const DELIMITERS          = [ ',', '\t', ';', '|', ':' ];
	protected const SUPPORTED_ENCODINGS = [
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

	/**
	 * @param $document
	 *
	 * @throws \League\Csv\Exception
	 */
	public function __construct( $document ) {
		if ( ! ini_get( 'auto_detect_line_endings' ) ) {
			ini_set( 'auto_detect_line_endings', '1' );
		}

		$this->csv = static::initReader( $document );
		$this->csv->setDelimiter( $this->delimiter( $this->csv->getContent() ) );
		$this->csv->setHeaderOffset( 0 );

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
	 * Automatically encode the content.
	 *
	 * @param string $to
	 *
	 * @return $this
	 */
	public function autoEncode( $to = 'utf-8' ) {
		return $this->addCharsetConversion(
			mb_detect_encoding( mb_substr( $this->csv->getContent(), 0, 1024 ), static::SUPPORTED_ENCODINGS ),
			$to
		);
	}

	/**
	 * @return array|string[]
	 */
	public function getHeader() {
		return $this->csv->getHeader();
	}

	/**
	 * @param int $offset
	 *
	 * @throws \League\Csv\Exception
	 */
	public function setHeaderOffset( $offset = 0 ) {
		$this->csv->setHeaderOffset( $offset );
	}

	/**
	 * @param $document
	 *
	 * @return Reader
	 */
	public static function make( $document ) {
		return new self( $document );
	}

	/**
	 * @return array
	 */
	public function get() {
		$items = [];

		foreach ( $this->csv as $item ) {
			$items[] = array_map( 'trim', $item );
		}

		return $items;
	}

	/**
	 * @param callable $callback
	 */
	public function stream( callable $callback ) {
		foreach ( $this->csv as $item ) {
			$callback( array_map( 'trim', $item ) );
		}
	}

	/**
	 * @return \Iterator|\League\Csv\Reader
	 */
	public function getIterable() {
		return $this->csv;
	}

	/**
	 * @param $document
	 *
	 * @return \League\Csv\Reader
	 */
	protected static function initReader( $document ) {
		if ( $document === null ) {
			throw new InvalidCSVException( 'No document specified' );
		}

		if ( $document instanceof \SplFileObject ) {
			return \League\Csv\Reader::createFromPath( $document );
		}

		if ( \is_resource( $document ) ) {
			return \League\Csv\Reader::createFromStream( $document );
		}

		if ( file_exists( $document ) ) {
			return \League\Csv\Reader::createFromPath( $document );
		}

		return \League\Csv\Reader::createFromString( $document );
	}

	/**
	 * @param     $content
	 * @param int $linesToCheck
	 *
	 * @return mixed
	 */
	protected static function delimiter( $content, $linesToCheck = 5 ) {
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

		$results = array_keys( $results, max( $results ) );

		return $results[0];
	}
}