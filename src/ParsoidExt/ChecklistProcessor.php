<?php

namespace MediaWiki\Extension\Checklists\ParsoidExt;

use MediaWiki\Extension\Checklists\ParsoidListItemProvider;
use MediaWiki\Extension\Checklists\WikiTextPostProcessor;
use Wikimedia\Parsoid\DOM\Element as DOMElement;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Ext\DOMProcessor;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;

class ChecklistProcessor extends DOMProcessor {

	/**
	 * Post-process DOM in the wt2html direction.
	 *
	 * @param ParsoidExtensionAPI $extApi
	 * @param DocumentFragment|Element $root The root of the tree to process
	 * @param array $options
	 * @param bool $atTopLevel Is this processor invoked on the top level page?
	 * If false, this is being invoked in a sub-pipeline (ex: extensions)
	 */
	public function wtPostprocess(
		ParsoidExtensionAPI $extApi,
		Node $root,
		array $options,
		bool $atTopLevel
	): void {
		$wikiTextPostprocessor = new WikiTextPostProcessor( new ParsoidListItemProvider() );
		$wikiTextPostprocessor->processDOM( $root );
	}

	/**
	 * Pre-process DOM in the html2wt direction.
	 *
	 * @param ParsoidExtensionAPI $extApi
	 * @param Element $root
	 */
	public function htmlPreprocess(
		ParsoidExtensionAPI $extApi,
		DOMElement $root
	): void {
		$nodes = $root->getElementsByTagName( 'ul' );
		$checklistNodes = [];
		foreach ( $nodes as $node ) {
			if ( $node->getAttribute( 'class' ) === 'checklist' ) {
				$checklistNodes[] = $node;
			}
		}

		foreach ( $checklistNodes as $checklistNode ) {
			$childElement = $this->getChildElementsFromNode( $checklistNode );

			$total = count( $childElement );
			$count = 0;
			$newParagraphElement = $root->ownerDocument->createElement( 'p' );
			$elements = [];
			foreach ( $childElement as $element ) {
				$count++;
				$prefix = '[] ';
				if ( isset( $element[1] ) ) {
					$prefix = '[x] ';
				}

				$childNodes = $element[0]->childNodes;
				$textElement = $root->ownerDocument->createTextNode( $prefix );

				$elements[] = $textElement;

				foreach ( $childNodes as $child ) {
					$elements[] = $child;
				}

				if ( $count != $total ) {
					$newLineText = $root->ownerDocument->createTextNode( " \n" );
					$elements[] = $newLineText;
				}
			}

			$newParagraphElement = $root->ownerDocument->createElement( 'p' );

			$newParagraphElement = $this->appendChildren( $newParagraphElement, $elements );
			$checklistNode->parentNode->replaceChild( $newParagraphElement, $checklistNode );
		}
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

	/**
	 *
	 * @param DomNode $element
	 * @return array
	 */
	private function getChildElementsFromNode( $element ) {
		$childElement = [];
		$elements = $element->childNodes;
		foreach ( $elements as $child ) {
			if ( $child->nodeType === XML_TEXT_NODE ) {
				continue;
			}
			$classes = $child->getAttribute( 'class' );
			if ( strpos( $classes, 'checklist-checked' ) !== false ) {
				$childElement[] = [ $child, 'x' ];
			} else {
				$childElement[] = [ $child ];
			}
		}
		return $childElement;
	}
}
