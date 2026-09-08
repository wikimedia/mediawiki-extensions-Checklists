<?php

namespace MediaWiki\Extension\Checklists\HookHandler;

use DOMDocument;
use Exception;
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

class ModifyOutput implements ParserBeforeInternalParseHook, ParserAfterTidyHook {

	private const UNSUPPORTED_NAMESPACES = [ NS_FILE, NS_TEMPLATE ];

	/**
	 * Parsed items per parse run, keyed by the object ID of the ParserOutput of that run.
	 * Nested parses (message parsing, extensions using their own Parser instance) must not
	 * consume the items of the enclosing parse.
	 *
	 * @var array<int,array>
	 */
	private $items = [];

	/** @var ChecklistManager */
	private $manager;

	/**
	 * @param ChecklistManager $manager
	 */
	public function __construct( ChecklistManager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * @inheritDoc
	 */
	public function onParserBeforeInternalParse( $parser, &$text, $stripState ) {
		$parseId = $this->getParseId( $parser );
		if ( isset( $this->items[$parseId] ) ) {
			// Text containing checklists already processed for this parse run
			return;
		}
		$title = $this->titleFromPageReference( $parser->getPage() );

		if ( $title === null ) {
			return;
		}

		if ( !$this->isContentModelSuitable( $title ) || !$text ) {
			return;
		}

		$this->items[$parseId] = $this->manager->getParser()->parse( $text, $title, true );

		if ( !empty( $this->items[$parseId] ) && !$this->isNamespaceSuitable( $title ) ) {
			$this->showUnsupportedPageNotice( $parser, $text );
			$this->items[$parseId] = [];
		}
	}

	/**
	 * @inheritDoc
	 */
	public function onParserAfterTidy( $parser, &$text ) {
		$parseId = $this->getParseId( $parser );
		$items = $this->items[$parseId] ?? [];
		unset( $this->items[$parseId] );
		if ( !$items ) {
			return;
		}
		$document = new DOMDocument();
		$this->sanitizeText( $text );
		// phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
		@$document->loadHTML(
			"<!DOCTYPE html><html><head><meta charset=\"UTF-8\"></head><body><div>$text</div></body></html>"
		);

		$body = $document->getElementsByTagName( 'body' )->item( 0 );
		$root = $body->firstChild;

		$wikiTextPostprocessor = new WikiTextPostProcessor( new ListItemProvider() );
		$wikiTextPostprocessor->processDOM( $root );

		$checklistElements = $this->getChecklistElements( $document );
		$keys = array_keys( $items );
		$hasChecklist = false;
		$index = 0;
		foreach ( $checklistElements as $checklistEl ) {
			$hasChecklist = true;
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
		if ( $hasChecklist ) {
			$parser->getOutput()->addModules( [ 'ext.checklists.view' ] );
			$parser->getOutput()->addModuleStyles( [ 'ext.checklists.styles' ] );
		}

		$newText = $document->saveHTML( $root );
		$this->unSanitizeText( $newText );
		$text = preg_replace( '#^<div>(.*?)</div>$#si', '$1', $newText );
	}

	/**
	 * Identifies a single parse run. Every Parser::parse() call creates a fresh ParserOutput,
	 * so nested parse runs get their own state.
	 *
	 * @param Parser $parser
	 * @return int
	 */
	private function getParseId( Parser $parser ): int {
		return spl_object_id( $parser->getOutput() );
	}

	/**
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
				if ( $element->nodeType !== XML_ELEMENT_NODE || $element->nodeName !== 'li' ) {
					continue;
				}
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

	/**
	 * @param string &$text
	 * @return void
	 */
	private function sanitizeText( string &$text ) {
		// Find all tags like `<mw:...>`/`</mw:...>` and convert to `<MW___...>`/`</MW___...>`
		$text = preg_replace_callback(
			'/([<\/])mw:([a-z]+)([^>]*)>/i',
			static function ( $matches ) {
				return $matches[1] . 'MW___' . strtoupper( $matches[2] ) . $matches[3] . '>';
			},
			$text
		);
	}

	/**
	 * @param string &$text
	 * @return void
	 */
	private function unSanitizeText( string &$text ) {
		// Find all tags like `<MW___...>`/`</MW___...>` and convert to `<mw:...>`/`</mw:...>`
		$text = preg_replace_callback(
			'/(<|<\/)MW___([A-Z]+)([^>]*)>/i',
			static function ( $matches ) {
				return $matches[1] . 'mw:' . strtolower( $matches[2] ) . $matches[3] . '>';
			},
			$text
		);
	}

}
