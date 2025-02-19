<?php

namespace MediaWiki\Extension\Checklists;

use DateTime;
use MediaWiki\CommentStore\CommentStoreComment;
use MediaWiki\Content\WikitextContent;
use MediaWiki\Context\RequestContext;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\PageUpdaterFactory;
use MediaWiki\User\User;
use MediaWiki\User\UserIdentity;
use PermissionsError;

class ChecklistManager {
	/** @var ChecklistParser */
	private $parser;

	/** @var ChecklistStore */
	private $store;

	/** @var RevisionStore */
	private $revisionStore;

	/** @var PageUpdaterFactory */
	private $pageUpdaterFactory;

	/**
	 * @param ChecklistParser $parser
	 * @param ChecklistStore $store
	 * @param RevisionStore $revisionStore
	 * @param PageUpdaterFactory $pageUpdaterFactory
	 * @param PermissionManager $permissionManager
	 */
	public function __construct(
		ChecklistParser $parser, ChecklistStore $store,
		RevisionStore $revisionStore, PageUpdaterFactory $pageUpdaterFactory,
		private readonly PermissionManager $permissionManager
	) {
		$this->parser = $parser;
		$this->store = $store;
		$this->revisionStore = $revisionStore;
		$this->pageUpdaterFactory = $pageUpdaterFactory;
	}

	/**
	 * @return ChecklistParser
	 */
	public function getParser(): ChecklistParser {
		return $this->parser;
	}

	/**
	 * @return ChecklistStore
	 */
	public function getStore(): ChecklistStore {
		return $this->store;
	}

	/**
	 * @param RevisionRecord $revisionRecord
	 * @param UserIdentity $user
	 *
	 * @return array|null [ 'deleted' => [], 'updated' => [], 'created' => [] ]
	 */
	public function persistsOnSave( RevisionRecord $revisionRecord, UserIdentity $user ): ?array {
		$content = $revisionRecord->getContent( SlotRecord::MAIN );
		if ( !( $content instanceof WikitextContent ) ) {
			return null;
		}
		$freshItemData = $this->parser->parse(
			$content->getText(), $revisionRecord->getPage()
		);

		$existing = $this->store->forPageIdentity( $revisionRecord->getPage() )->query();
		$existingIds = array_map( static function ( ChecklistItem $item ) {
			return $item->getId();
		}, $existing );
		$freshItemIds = array_keys( $freshItemData );

		$toDelete = array_diff( $existingIds, $freshItemIds );
		$toAdd = array_diff( $freshItemIds, $existingIds );
		$toUpdate = array_intersect( $freshItemIds, $existingIds );

		$result = [ 'deleted' => [], 'updated' => [], 'created' => [] ];
		foreach ( $existing as $item ) {
			if ( in_array( $item->getId(), $toDelete ) ) {
				$this->store->delete( $item );
				$result['deleted'][] = $item;
			} elseif ( in_array( $item->getId(), $toUpdate ) ) {
				$updatedItem = clone $item;
				$updatedItem->setValue( $freshItemData[$item->getId()]['value'] );
				$updatedItem->setText( $freshItemData[$item->getId()]['text'] );
				if ( $item->getHash() !== $updatedItem->getHash() ) {
					$this->store->persist( $updatedItem );
					$result['updated'][] = $updatedItem;
				}
			}
		}
		foreach ( $toAdd as $idToAdd ) {
			$newItem = $this->newChecklistItemFromData(
				$freshItemData[$idToAdd], $revisionRecord->getPage(), $user
			);
			$this->store->persist( $newItem );
			$result['created'][] = $newItem;
		}

		return $result;
	}

	/**
	 * @param ChecklistItem $item
	 * @param string $value
	 * @param User $user
	 *
	 * @return RevisionRecord|null
	 * @throws \MWException
	 */
	public function setStatusForChecklistItem( ChecklistItem $item, string $value, User $user ): ?RevisionRecord {
		$page = $item->getPage();
		$this->assertCanUpdate( $user, $page );
		$revisionRecord = $this->revisionStore->getRevisionByTitle( $page );
		if ( !$revisionRecord ) {
			return null;
		}
		$content = $revisionRecord->getContent( SlotRecord::MAIN );
		if ( !( $content instanceof WikitextContent ) ) {
			return null;
		}
		$text = $content->getText();
		$newText = $this->parser->setItemValue( $text, $item->getId(), $value, $page );

		if ( md5( $text ) === md5( $newText ) ) {
			return $revisionRecord;
		}
		return $this->pageUpdaterFactory->newPageUpdater( $page, $user )
			->setContent( SlotRecord::MAIN, new WikitextContent( $newText ) )
			->saveRevision( CommentStoreComment::newUnsavedComment( 'Checklist item status changed' ) );
	}

	/**
	 * @param array $data
	 * @param PageIdentity $page
	 * @param UserIdentity $author
	 *
	 * @return ChecklistItem
	 */
	private function newChecklistItemFromData( array $data, PageIdentity $page, UserIdentity $author ): ChecklistItem {
		return new ChecklistItem(
			$data['id'],
			$data['text'],
			$data['value'],
			$page,
			new DateTime( 'now' ),
			new DateTime( 'now' ),
			$author
		);
	}

	/**
	 * @param User $user
	 * @param PageIdentity $page
	 * @return void
	 * @throws PermissionsError
	 */
	private function assertCanUpdate( User $user, PageIdentity $page ) {
		RequestContext::getMain()->setActionName( 'edit' );
		$status = $this->permissionManager->getPermissionStatus( 'edit', $user, $page );
		if ( !$status->isOK() ) {
			throw new PermissionsError( 'edit', $status );
		}
	}
}
