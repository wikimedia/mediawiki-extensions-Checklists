<?php

namespace MediaWiki\Extension\Checklists;

use Exception;
use MediaWiki\Page\PageIdentity;

class ChecklistParser {

	/**
	 * @var string[]
	 */
	private $matchers = [
		'check' => 'parseCheck'
	];

	/**
	 * @var string[]
	 */
	private $setters = [
		'check' => 'setCheckValue'
	];

	/**
	 * @param string $text
	 * @param PageIdentity $target
	 * @param bool|null $returnLine
	 *
	 * @return array
	 */
	public function parse( string $text, PageIdentity $target, ?bool $returnLine = false ): array {
		$lines = explode( "\n", $text );
		$checklist = [];
		foreach ( $lines as $line ) {
			$item = $this->parseLine( $line, $target, $returnLine );
			if ( is_array( $item ) ) {
				$checklist[$item['id']] = $item;
			}
		}
		return $checklist;
	}

	/**
	 * @param string $text Text of the page where checklist is
	 * @param string $id
	 * @param string $value
	 * @param PageIdentity $page
	 *
	 * @return string
	 */
	public function setItemValue( string $text, string $id, string $value, PageIdentity $page ): string {
		$items = $this->parse( $text, $page, true );

		foreach ( $items as $item ) {
			if ( $item['id'] === $id ) {
				$setter = $this->setters[$item['type']];
				$modified = $this->$setter( $item, $value );
				$pattern = preg_quote( $item['line'] );
				$pattern = "/$pattern\n/s";
				$text = preg_replace( $pattern, $modified . "\n", $text );
			}
		}

		return $text;
	}

	/**
	 * Check if there are duplicate items in the checklist, and modify the text to deduplicate
	 *
	 * @param string $text
	 * @param PageIdentity $page
	 *
	 * @return array|null
	 */
	public function deduplicate( string $text, PageIdentity $page ): string {
		$duplicates = [];
		$lines = explode( "\n", $text );
		foreach ( $lines as &$line ) {
			$item = $this->parseLine( $line, $page, true );
			if ( !is_array( $item ) ) {
				continue;
			}
			if ( !isset( $duplicates[$item['id']] ) ) {
				$duplicates[$item['id']] = 1;
				continue;
			}
			$duplicates[$item['id']]++;
			$line .= " (" . $duplicates[$item['id']] . ")";

		}
		return implode( "\n", $lines );
	}

	/**
	 * @param string $line
	 * @param PageIdentity $target
	 * @param bool $returnLine
	 *
	 * @return array|null
	 * @throws Exception
	 */
	private function parseLine( string $line, PageIdentity $target, bool $returnLine ): ?array {
		foreach ( $this->matchers as $type => $matcher ) {
			$value = $this->$matcher( $line, $target, $returnLine );
			if ( is_array( $value ) ) {
				$value['type'] = $type;
				return $value;
			}
		}
		return null;
	}

	/**
	 * @param string $line
	 * @param PageIdentity $target
	 * @param bool $returnLine
	 *
	 * @return array|null
	 * @throws Exception
	 */
	private function parseCheck( string $line, PageIdentity $target, bool $returnLine ): ?array {
		$matches = [];

		// Match:
		// [] Text
		// [x] Text
		// [X] Text
		preg_match( '/^(\|?)(\[([xX])?\])\s*(.*)$/', $line, $matches );
		if ( empty( $matches ) ) {
			return null;
		}
		$checked = strtolower( $matches[3] ?? '' ) === 'x';
		$text = trim( $matches[4] );
		$item = [ 'id' => $this->getId( $text, $target ), 'text' => $text, 'value' => $checked ];
		if ( $returnLine ) {
			if ( strpos( $line, '|' ) === 0 ) {
				$line = substr( $line, 1 );
			}
			$item['line'] = $line;
		}
		return $item;
	}

	/**
	 * @param array $item
	 * @param string $value
	 *
	 * @return string
	 */
	private function setCheckValue( array $item, string $value ): string {
		$value = (bool)( $value === 'checked' ? 1 : (int)$value );
		$checked = $value ? 'x' : '';
		return "[{$checked}] {$item['text']}";
	}

	/**
	 * Parse the checklist item text and return a unique ID for the item.
	 * @param string $text
	 * @param PageIdentity $target
	 *
	 * @return string
	 * @throws Exception
	 */
	private function getId( string $text, PageIdentity $target ): string {
		$chunks = preg_split( '/(\[\[.*?\]\])/', $text, -1, PREG_SPLIT_DELIM_CAPTURE );
		$rest = [];
		$links = [];
		foreach ( $chunks as $chunk ) {
			if ( preg_match( '/^\[\[(.*?)(\|.*?|)\]\]$/', $chunk ) ) {
				// Preserve links, they are not case-insensitive nor sortable
				$links[] = preg_replace( '/^\[\[(.*?)(\|.*?|)\]\]$/', '$1', $chunk );
			} else {
				$rest[] = $chunk;
			}

		}

		$rest = implode( '', $rest );
		// Remove whitespaces, commas, periods
		$rest = preg_replace( '/[\s+,\.\!\?]/', '', $rest );
		$rest = strip_tags( $rest );
		// Lowercase
		$rest = strtolower( $rest );
		// Sort all chars into alphabetical order
		$rest = str_split( $rest );
		sort( $rest );
		$rest = implode( '', $rest );
		$rest = trim( $rest );
		$base = $rest . implode( '', $links );

		// Hash
		return md5( $base . '#' . $target->getId() );
	}
}
