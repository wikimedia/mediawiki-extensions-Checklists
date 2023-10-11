<?php

namespace MediaWiki\Extension\Checklists;

use DateTime;
use MediaWiki\Page\PageIdentity;
use MediaWiki\User\UserFactory;
use stdClass;
use TitleFactory;
use User;
use Wikimedia\Rdbms\ILoadBalancer;

class ChecklistStore {
	/** @var ILoadBalancer */
	private $lb;

	/** @var UserFactory */
	private $userFactory;

	/** @var TitleFactory */
	private $titleFactory;

	/** @var array */
	private $conds = [];

	/**
	 * @param ILoadBalancer $loadBalancer
	 * @param UserFactory $userFactory
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( ILoadBalancer $loadBalancer, UserFactory $userFactory, TitleFactory $titleFactory ) {
		$this->lb = $loadBalancer;
		$this->userFactory = $userFactory;
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @param string $id
	 *
	 * @return $this
	 */
	public function id( string $id ): ChecklistStore {
		$this->conds['ci_id'] = $id;
		return $this;
	}

	/**
	 * @param User $user
	 *
	 * @return $this
	 */
	public function forUser( User $user ): ChecklistStore {
		if ( $user->isRegistered() ) {
			$this->conds['ci_user'] = $user->getId();
		}
		return $this;
	}

	/**
	 * @param PageIdentity $page
	 *
	 * @return $this
	 */
	public function forPageIdentity( PageIdentity $page ): ChecklistStore {
		if ( $page->exists() ) {
			$this->conds['ci_page'] = $page->getId();
		}
		return $this;
	}

	/**
	 * @param array|null $conds
	 *
	 * @return ChecklistItem[]
	 */
	public function query( $conds = [] ): array {
		$db = $this->lb->getConnection( DB_PRIMARY );
		$res = $db->select(
			[ 'ci' => 'checklist_items', 'p' => 'page' ],
			[ 'ci.*', 'p.page_title', 'p.page_id', 'p.page_namespace' ],
			array_merge( $this->conds, $conds, [ 'page_id = ci_page' ] ),
			__METHOD__
		);
		$this->conds = [];

		$items = [];
		foreach ( $res as $row ) {
			$items[] = $this->newFromRow( $row );
		}
		return $items;
	}

	/**
	 * @param ChecklistItem $item
	 *
	 * @return bool
	 */
	public function persist( ChecklistItem $item ): bool {
		$db = $this->lb->getConnection( DB_PRIMARY );
		if ( !$item->getId() ) {
			return false;
		}
		if ( $this->exists( $item->getId() ) ) {
			return $db->update(
				'checklist_items',
				$this->dbSerialize( $item ),
				[ 'ci_id' => $item->getId() ],
				__METHOD__
			);
		} else {
			$re = $db->insert(
				'checklist_items',
				array_merge( [ 'ci_id' => $item->getId() ], $this->dbSerialize( $item ) ),
				__METHOD__
			);
			return $re;
		}
	}

	/**
	 * @param ChecklistItem $item
	 *
	 * @return array
	 */
	private function dbSerialize( ChecklistItem $item ): array {
		return [
			'ci_text' => $item->getText(),
			'ci_value' => $item->getValue(),
			'ci_page' => $item->getPage()->getArticleId(),
			'ci_author' => $item->getAuthor()->getId(),
			'ci_created' => $item->getCreated()->format( 'YmdHis' ),
			'ci_touched' => $item->getModified()->format( 'YmdHis' ),
			'ci_hash' => $item->getHash(),
		];
	}

	/**
	 * @param string $id
	 *
	 * @return bool
	 */
	private function exists( string $id ): bool {
		$db = $this->lb->getConnection( DB_REPLICA );
		return (bool)$db->selectField( 'checklist_items', 'ci_id', [ 'ci_id' => $id ], __METHOD__ );
	}

	/**
	 * @param stdClass $row
	 *
	 * @return ChecklistItem
	 */
	private function newFromRow( stdClass $row ): ChecklistItem {
		return new ChecklistItem(
			$row->ci_id,
			$row->ci_text,
			$row->ci_value,
			$this->titleFactory->newFromRow( $row ),
			DateTime::createFromFormat( 'YmdHis', $row->ci_created ),
			DateTime::createFromFormat( 'YmdHis', $row->ci_touched ),
			$this->userFactory->newFromId( $row->ci_author )
		);
	}

	/**
	 * @param ChecklistItem $item
	 *
	 * @return bool
	 */
	public function delete( ChecklistItem $item ): bool {
		$db = $this->lb->getConnection( DB_PRIMARY );
		return $db->delete(
			'checklist_items',
			[ 'ci_id' => $item->getId() ],
			__METHOD__
		);
	}
}
