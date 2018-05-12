<?php namespace LasseRafn\CsvReader;

use League\Csv\CharsetConverter;

class Reader
{
	/** @var \Iterator|\League\Csv\Reader */
	protected $csv;
	protected const DELIMITERS = [ ',', '\t', ';', '|', ':' ];

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

	public function setHeaderOffset( $offset = 0 ) {
		$this->csv->setHeaderOffset( $offset );
	}

	public static function make( $document ) {
		return new self( $document );
	}

	public function get() {
		$items = [];

		foreach ( $this->csv as $item ) {
			$items[] = $item;
		}

		return $items;
	}

	protected static function initReader( $document ) {
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