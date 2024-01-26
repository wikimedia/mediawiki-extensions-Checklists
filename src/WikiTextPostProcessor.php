<?php

namespace MediaWiki\Extension\Checklists;

class WikiTextPostProcessor {

	/** @var array */
	private const TAGS_TO_CHECK = [ 'p', 'td' ];

	/** @var DOMNode */
	private $root;

	/** @var bool */
	private $inTable = false;

	/** @var IListItemProvider */
	private $listItemProvider = null;

	/**
	 * @param IListItemProvider $listItemProvider
	 */
	public function __construct( IListItemProvider $listItemProvider ) {
		$this->listItemProvider = $listItemProvider;
	}

	/**
	 *
	 * @param DOMNode $document
	 * @return void
	 */
	public function processDOM( $document ) {
		if ( $document->nodeType != 1 ) {
			return;
		}
		$this->root = $document;
		foreach ( static::TAGS_TO_CHECK as $tag ) {
			$this->inTable = $this->isTableCell( $tag );
			$els = $this->root->getElementsByTagName( $tag );
			$nonLiveEls = [];
			foreach ( $els as $el ) {
				$nonLiveEls[] = $el;
			}

			foreach ( $nonLiveEls as $parent ) {
				$hasChecklist = $this->hasChecklistNodes( $parent );
				if ( $hasChecklist === false ) {
					continue;
				}
				$allNewElements = $this->getChecklistElements( $parent );
				$firstElementIsChecklist = $this->checkForChecklistElements(
					$parent->childNodes->item( 0 )->textContent
				);

				if ( !$firstElementIsChecklist ) {
					$textOutsideChecklist = $this->getNonChecklistElements( $parent );
					if ( $textOutsideChecklist->textContent !== '' ) {
						$parent->parentNode->insertBefore( $textOutsideChecklist, $parent );
					}
				}
				if ( $this->inTable && $firstElementIsChecklist ) {
					$children = $parent->childNodes;
					$nonLiveChildren = [];
					foreach ( $children as $child ) {
						$nonLiveChildren[] = $child;
					}
					if ( count( $children ) > 0 ) {
						while ( $parent->hasChildNodes() ) {
							$parent->removeChild( $parent->lastChild );
						}
					}

					$this->insertChecklistElements( $allNewElements, $parent );
					$first = true;
					foreach ( $nonLiveChildren as $child ) {
						if ( $first || $child->nodeType === XML_TEXT_NODE ) {
							$first = false;
							continue;
						}
						$parent->appendChild( $child );
					}
					continue;
				}
				$this->insertChecklistElements( $allNewElements, $parent );
			}
		}
	}

	/**
	 *
	 * @param string $tag
	 * @return bool
	 */
	private function isTableCell( $tag ) {
		return ( $tag === 'td' );
	}

	/**
	 * Check if element contains checklists
	 *
	 * @param DomNode $parent
	 * @return bool
	 */
	private function hasChecklistNodes( $parent ) {
		$parentText = $parent->textContent;
		$clearedTextEl = explode( "\n", $parentText );
		$hasChecklistInside = false;

		foreach ( $clearedTextEl as $clearedText ) {
			if ( preg_match( '/^\[ *x? *\]/', $clearedText ) ) {
				$hasChecklistInside = true;
			}
		}

		return $hasChecklistInside;
	}

	/**
	 * Get elements before checklist in one paragraph
	 *
	 * @param DomNode $parent
	 * @return DomNode
	 */
	private function getNonChecklistElements( $parent ) {
		$allNonChecklistEls = [];
		$childNodes = $parent->childNodes;

		foreach ( $childNodes as $node ) {
			$text = $node->textContent;
			$text = trim( $text );
			if ( !preg_match( '/(^|\n)\[ *x? *\]/', $text ) ) {
				$allNonChecklistEls[] = $node;
				continue;
			}
			break;
		}

		$newParagraph = $this->root->ownerDocument->createElement( 'p' );
		$this->appendChildren( $newParagraph, $allNonChecklistEls );
		return $newParagraph;
	}

	/**
	 * Get all checklist list elements in an array
	 *
	 * @param DomNode $parent
	 * @return array
	 */
	private function getChecklistElements( $parent ) {
		$allNewElements = [];
		$allElements = $parent->childNodes;

		$childElements = [];
		$listItemEl = null;
		foreach ( $allElements as $element ) {
			if ( $element->nodeType !== XML_TEXT_NODE ) {
				if ( $element->nodeName === 'ul' ) {
					break;
				}
				$childElements[] = $element;
			} else {
				$elementText = $element->wholeText;
				$checklistTexts = explode( "\n", $elementText );
				foreach ( $checklistTexts as $text ) {
					$matches = $this->checkForChecklistElements( $text );
					if ( !$matches ) {
						if ( !$text ) {
							continue;
						}
						$child = $this->root->ownerDocument->createTextNode( $text );
						$childElements[] = $child;
						continue;
					}
					if ( $listItemEl ) {
						$listItemEl = $this->appendChildren( $listItemEl, $childElements );
						$allNewElements[] = $listItemEl;
					}

					$childElements = [];
					$listItemEl = $this->listItemProvider->createNewListItem( $this->root, $matches[0], $text );
				}
			}
		}

		if ( $listItemEl ) {
			$listItemEl = $this->appendChildren( $listItemEl, $childElements );
			$allNewElements[] = $listItemEl;
		}
		return $allNewElements;
	}

	/**
	 *
	 * @param array $allNewElements
	 * @param DomNode $parent
	 * @return void
	 */
	private function insertChecklistElements( $allNewElements, $parent ) {
		$checklistEl = $this->listItemProvider->createChecklistElement( $this->root );
		foreach ( $allNewElements as $element ) {
			if ( $element->nodeName !== 'p' ) {
				$checklistEl->appendChild( $element );
			}
		}
		if ( $this->inTable ) {
			if ( $parent->nodeValue !== '' && $parent->nodeType !== null ) {
				$parent->nodeValue = '';
			}
			$parent->appendChild( $checklistEl );
		} else {
			$parent->parentNode->replaceChild( $checklistEl, $parent );
		}
	}

	/**
	 * Checks if a string contains checklist elements
	 *
	 * @param string $element
	 * @return array
	 */
	private function checkForChecklistElements( $element ) {
		preg_match( '/^\[ *x? *\]/', $element, $matches );
		return $matches;
	}

	/**
	 * Add elements from a DomNodeList as children to a DomNode element
	 *
	 * @param DomNode $element
	 * @param DomNodeList $children
	 * @return DomNode
	 */
	private function appendChildren( $element, $children ) {
		foreach ( $children as $child ) {
			if ( !$child ) {
				continue;
			}
			$element->appendChild( $child );
		}
		return $element;
	}
}
