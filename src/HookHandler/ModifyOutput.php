<?php

namespace MediaWiki\Extension\Checklists\HookHandler;

use DOMDocument;
use MediaWiki\Extension\Checklists\ChecklistManager;
use MediaWiki\Extension\Checklists\ListItemProvider;
use MediaWiki\Extension\Checklists\WikiTextPostProcessor;
use MediaWiki\Hook\ParserAfterTidyHook;
use MediaWiki\Hook\ParserBeforeInternalParseHook;
use MediaWiki\Page\PageReference;
use Message;
use OOUI\HtmlSnippet;
use OOUI\MessageWidget;
use OutputPage;
use Parser;
use Title;

class ModifyOutput implements ParserBeforeInternalParseHook, ParserAfterTidyHook {

	private const UNSUPPORTED_NAMESPACES = [ NS_MEDIAWIKI, NS_FILE, NS_TEMPLATE ];

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

	/** @var bool */
	private $itemLookupDone = false;

	/**
	 * @inheritDoc
	 */
	public function onParserBeforeInternalParse( $parser, &$text, $stripState ) {
		if ( $this->itemLookupDone ) {
			return;
		}
		$this->items = [];
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
		if ( $this->items && !$this->isNamespaceSuitable( $this->titleFromPageReference( $parser->getPage() ) ) ) {
			$this->items = [];
			$this->showUnsupportedPageNotice( $parser, $text );
			return;
		}
		$parser->getOutput()->addModules( [ 'ext.checklists.view' ] );
		$parser->getOutput()->addModuleStyles( [ 'ext.checklists.styles' ] );
		$this->itemLookupDone = true;
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function onParserAfterTidy( $parser, &$text ) {
		if ( empty( $this->items ) ) {
			return;
		}
		$document = new DOMDocument();
		$document->loadHTML( "<html><head><meta charset=\"UTF-8\"></head><body><div>$text</div></body></html>" );
		$body = $document->getElementsByTagName( 'body' )->item( 0 );
		$root = $body->firstChild;

		$wikiTextPostprocessor = new WikiTextPostProcessor( new ListItemProvider() );
		$wikiTextPostprocessor->processDOM( $root );

		$checklistElements = $this->getChecklistElements( $document );
		foreach ( $checklistElements as $index => $checklistEl ) {
			$checklistEl->setAttribute( 'data-checklist-item-id', $this->items[$index]['id'] );
			$checklistEl->setAttribute( 'data-value', $this->items[$index]['value'] ? '1' : '0' );
		}

		$newText = $document->saveHTML( $root );
		$text = preg_replace( '#^<div>(.*?)</div>$#si', '$1', $newText );
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
