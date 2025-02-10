<?php

namespace MediaWiki\Extension\Checklists\HookHandler;

use DOMDocument;
use MediaWiki\Extension\Checklists\ChecklistManager;
use MediaWiki\Extension\Checklists\ListItemProvider;
use MediaWiki\Extension\Checklists\WikiTextPostProcessor;
use MediaWiki\Hook\ParserAfterTidyHook;
use MediaWiki\Hook\ParserBeforeInternalParseHook;
use MediaWiki\Message\Message;
use MediaWiki\Output\OutputPage;
use MediaWiki\Page\PageReference;
use MediaWiki\Parser\Parser;
use MediaWiki\Title\Title;
use OOUI\HtmlSnippet;
use OOUI\MessageWidget;
use Wikimedia\AtEase\AtEase;

class ModifyOutput implements ParserBeforeInternalParseHook, ParserAfterTidyHook {

	private const UNSUPPORTED_NAMESPACES = [ NS_FILE, NS_TEMPLATE ];

	/** @var array */
	private $items = [];

	/** @var ChecklistManager */
	private $manager;

	/**
	 *
	 * @param ChecklistManager $manager
	 */
	public function __construct( ChecklistManager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * @inheritDoc
	 */
	public function onParserBeforeInternalParse( $parser, &$text, $stripState ) {
		$title = $this->titleFromPageReference( $parser->getPage() );

		if ( $title === null ) {
			return;
		}

		if ( !$this->isContentModelSuitable( $title ) || !$text ) {
			return;
		}

		$this->items = $this->manager->getParser()->parse(
			$text, $this->titleFromPageReference( $parser->getPage() ), true
		);

		if ( !empty( $this->items ) && !$this->isNamespaceSuitable( $this->titleFromPageReference( $parser->getPage() ) ) ) {
			$this->showUnsupportedPageNotice( $parser, $text );
			return;
		}
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function onParserAfterTidy( $parser, &$text ) {
		if ( !$this->isNamespaceSuitable( $this->titleFromPageReference( $parser->getPage() ) ) ) {
			return;
		}
		$document = new DOMDocument();
		AtEase::suppressWarnings();
		$document->loadHTML(
			"<!DOCTYPE html><html><head><meta charset=\"UTF-8\"></head><body><div>$text</div></body></html>"
		);
		AtEase::restoreWarnings();

		$body = $document->getElementsByTagName( 'body' )->item( 0 );
		$root = $body->firstChild;

		$wikiTextPostprocessor = new WikiTextPostProcessor( new ListItemProvider() );
		$wikiTextPostprocessor->processDOM( $root );

		$checklistElements = $this->getChecklistElements( $document );
		$hasChecklist = false;
		foreach ( $checklistElements as $index => $checklistEl ) {
			$keys = array_keys( $this->items );
			$key = $keys[ $index ] ?? null;
			if ( !$key ) {
				continue;
			}

			$checklistEl->setAttribute( 'data-checklist-item-id', $this->items[ $key ]['id'] );
			$checklistEl->setAttribute( 'data-value', $this->items[$key]['value'] ? '1' : '0' );
			$hasChecklist = true;
		}
		if ( $hasChecklist ) {
			$parser->getOutput()->addModules( [ 'ext.checklists.view' ] );
			$parser->getOutput()->addModuleStyles( [ 'ext.checklists.styles' ] );
		}

		$newText = $document->saveHTML( $root );
		$text = preg_replace( '#^<div>(.*?)</div>$#si', '$1', $newText );
		$this->items = [];
	}

	/**
	 *
	 * @param DOMDocument $document
	 * @return array
	 */
	private function getChecklistElements( $document ) {
		$checklists = [];
		$checklistElements = [];
		$lists = $document->getElementsByTagName( 'ul' );

		foreach ( $lists as $element ) {
			if ( $element->getAttribute( 'class' ) === 'checklist' ) {
				$checklists[] = $element;
			}
		}
		foreach ( $checklists as $checklist ) {
			$elements = $checklist->childNodes;
			foreach ( $elements as $element ) {
				$checklistElements[] = $element;
			}
		}
		return $checklistElements;
	}

	/**
	 * Show a notice that checklist items are added on a page where they shouldnt be
	 *
	 * @param Parser $parser
	 * @param string &$text
	 *
	 * @return void
	 * @throws Exception
	 */
	private function showUnsupportedPageNotice( Parser $parser, &$text ) {
		// Special note to let people know checklists cannot go to templates
		OutputPage::setupOOUI();
		$parser->getOutput()->setEnableOOUI( true );
		$widget = new MessageWidget( [
			'label' => new HtmlSnippet( Message::newFromKey( 'checklists-not-allowed' )->parse() ),
			'type' => 'error',
		] );
		$text = $widget->toString() . "\n" . $text;
	}

	/**
	 * @param PageReference|null $page
	 *
	 * @return Title|null
	 */
	private function titleFromPageReference( ?PageReference $page ): ?Title {
		if ( $page instanceof PageReference ) {
			return Title::castFromPageReference( $page );
		}
		return null;
	}

	/**
	 * @param Title|null $title
	 *
	 * @return bool
	 */
	private function isNamespaceSuitable( ?Title $title ): bool {
		return $title instanceof Title && !in_array( $title->getNamespace(), static::UNSUPPORTED_NAMESPACES );
	}

	/**
	 * @param Title|null $title
	 *
	 * @return bool
	 */
	private function isContentModelSuitable( ?Title $title ): bool {
		return $title instanceof Title && $title->getContentModel() === CONTENT_MODEL_WIKITEXT;
	}
}
