<?php

namespace MediaWiki\Extension\Checklists;

use DOMDocument;
use DOMElement;

interface IListItemProvider {

	/**
	 * Build checklist list element from prefix with [] or [ x ]
	 * and task
	 *
	 * @param DOMDocument $document
	 * @param string $prefix
	 * @param string $text
	 * @return DOMElement
	 */
	public function createNewListItem( $document, $prefix, $text );

	/**
	 * Create checklist ul element
	 *
	 * @param DOMElement $document
	 * @return DOMElement
	 */
	public function createChecklistElement( $document );
}
