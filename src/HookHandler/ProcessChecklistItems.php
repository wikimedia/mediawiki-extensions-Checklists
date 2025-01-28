<?php

namespace MediaWiki\Extension\Checklists\HookHandler;

use ManualLogEntry;
use MediaWiki\Extension\Checklists\ChecklistManager;
use MediaWiki\Hook\ParserPreSaveTransformCompleteHook;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Html\Html;
use MediaWiki\Page\Hook\ArticleUndeleteHook;
use MediaWiki\Page\Hook\PageDeleteCompleteHook;
use MediaWiki\Page\Hook\PageDeleteHook;
use MediaWiki\Page\PageReference;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;
use StatusValue;

class ProcessChecklistItems implements
	ParserPreSaveTransformCompleteHook,
	PageSaveCompleteHook,
	PageDeleteCompleteHook,
	ArticleUndeleteHook,
	PageDeleteHook
{
	private const UNSUPPORTED_NAMESPACES = [ NS_MEDIAWIKI, NS_FILE, NS_TEMPLATE ];

	/** @var ChecklistManager */
	private $manager;

	/** @var HookContainer */
	private $hookContainer;

	/** @var RevisionStore */
	private $revisionStore;

	/** @var array */
	private $checksToDelete = [];

	/**
	 * @param ChecklistManager $manager
	 * @param HookContainer $hookContainer
	 * @param RevisionStore $revisionStore
	 */
	public function __construct(
		ChecklistManager $manager, HookContainer $hookContainer, RevisionStore $revisionStore
	) {
		$this->manager = $manager;
		$this->hookContainer = $hookContainer;
		$this->revisionStore = $revisionStore;
	}

	/**
	 * @inheritDoc
	 */
	public function onParserPreSaveTransformComplete( $parser, &$text ) {
		if ( !$this->isPageSuitable( $this->titleFromPageReference( $parser->getPage() ) ) || !$text ) {
			return;
		}
		// This will make sure that we do not have duplicate checklists
		// See docs/ChecklistItemID.md for more information
		$text = $this->manager->getParser()->deduplicate( $text, $this->titleFromPageReference( $parser->getPage() ) );
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	private function getItemHtml( array $item ): string {
		if ( $item['type'] === 'check' ) {
			return Html::element(
				'p',
				[
					'data-value' => $item['value'] ? '1' : '0',
					'class' => 'mw-checklist-item ' . ( $item['value'] ? 'checked' : '' ),
					'data-checklist-item-id' => $item['id'],
				], $item['text'] );
		}
		return '';
	}

	/**
	 * @inheritDoc
	 */
	public function onPageSaveComplete( $wikiPage, $user, $summary, $flags, $revisionRecord, $editResult ) {
		if ( !$this->isPageSuitable( $wikiPage->getTitle() ) ) {
			return;
		}
		$this->persistOnSave( $revisionRecord, $user );
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
	 * @param Title|null $title
	 *
	 * @return bool
	 */
	private function isPageSuitable( ?Title $title ): bool {
		return $title instanceof Title &&
			$this->isNamespaceSuitable( $title ) &&
			$this->isContentModelSuitable( $title );
	}

	/**
	 * @param ProperPageIdentity $page
	 * @param Authority $deleter
	 * @param string $reason
	 * @param int $pageID
	 * @param RevisionRecord $deletedRev
	 * @param ManualLogEntry $logEntry
	 * @param int $archivedRevisionCount
	 * @return void
	 */
	public function onPageDeleteComplete(
		ProperPageIdentity $page, Authority $deleter, string $reason, int $pageID,
		RevisionRecord $deletedRev, ManualLogEntry $logEntry, int $archivedRevisionCount
	) {
		if ( !$this->checksToDelete ) {
			return;
		}
		foreach ( $this->checksToDelete as $check ) {
			$this->manager->getStore()->delete( $check );
		}
		$this->hookContainer->run( 'ChecklistsItemsDeleted', [ $this->checksToDelete, $this->manager ] );
		$this->checksToDelete = [];
	}

	/**
	 * @inheritDoc
	 */
	public function onArticleUndelete( $title, $create, $comment, $oldPageId, $restoredPages ) {
		if ( !$this->isPageSuitable( $title ) ) {
			return;
		}
		$rev = $this->revisionStore->getRevisionByTitle( $title );
		if ( $rev ) {
			$this->persistOnSave( $rev, $rev->getUser() );
		}
	}

	/**
	 * @param ProperPageIdentity $page
	 * @param Authority $deleter
	 * @param string $reason
	 * @param StatusValue $status
	 * @param bool $suppress
	 * @return void
	 */
	public function onPageDelete(
		ProperPageIdentity $page, Authority $deleter, string $reason, StatusValue $status, bool $suppress
	) {
		$this->checksToDelete = $this->manager->getStore()->forPageIdentity( $page )->query();
	}

	/**
	 * @param RevisionRecord $revisionRecord
	 * @param UserIdentity $user
	 *
	 * @return void
	 */
	private function persistOnSave( RevisionRecord $revisionRecord, UserIdentity $user ) {
		$result = $this->manager->persistsOnSave( $revisionRecord, $user );
		if ( $result === null ) {
			return;
		}
		if ( !empty( $result['deleted'] ) ) {
			$this->hookContainer->run( 'ChecklistsItemsDeleted', [ $result['deleted'], $this->manager ] );
		}
		if ( !empty( $result['created'] ) ) {
			$this->hookContainer->run( 'ChecklistsItemsCreated', [ $result['created'], $this->manager ] );
		}
		if ( !empty( $result['updated'] ) ) {
			$this->hookContainer->run( 'ChecklistsItemsUpdated', [ $result['updated'], $this->manager ] );
		}
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
}
