<?php

namespace MediaWiki\Extension\Checklists;

/**
 * Sets checklist item IDs and values (as parsed from the wikitext) on rendered checklist items.
 * Works with both the legacy (\DOMDocument) and the Parsoid (\Dom\Document) DOM implementations.
 */
class ChecklistItemAnnotator {

	/**
	 * @param \DOMNode|\Dom\Node $root
	 * @return array List of `li` elements of all checklists within $root
	 */
	public function getChecklistItemElements( $root ): array {
		$checklistItemElements = [];
		$checklists = [];
		foreach ( $root->getElementsByTagName( 'ul' ) as $element ) {
			if ( $element->getAttribute( 'class' ) === 'checklist' ) {
				$checklists[] = $element;
			}
		}
		foreach ( $checklists as $checklist ) {
			foreach ( $checklist->childNodes as $element ) {
				// `nodeName` is upper-case for HTML elements in \Dom\Document
				if ( $element->nodeType !== XML_ELEMENT_NODE || strtolower( $element->nodeName ) !== 'li' ) {
					continue;
				}
				$checklistItemElements[] = $element;
			}
		}
		return $checklistItemElements;
	}

	/**
	 * Assign IDs and values of `$items` to the checklist item elements, in order of appearance
	 *
	 * @param array $checklistItemElements
	 * @param array $items As returned by ChecklistParser::parse
	 * @return void
	 */
	public function annotate( array $checklistItemElements, array $items ): void {
		$keys = array_keys( $items );
		$index = 0;
		foreach ( $checklistItemElements as $checklistEl ) {
			if ( $checklistEl->hasAttribute( 'data-checklist-item-id' ) ) {
				// Already assigned by a nested parse run (transclusion, DPL, ...)
				continue;
			}
			$key = $keys[ $index ] ?? null;
			if ( !$key ) {
				continue;
			}
			$index++;

			$checklistEl->setAttribute( 'data-checklist-item-id', $items[ $key ]['id'] );
			$checklistEl->setAttribute( 'data-value', $items[ $key ]['value'] ? '1' : '0' );
		}
	}
}
