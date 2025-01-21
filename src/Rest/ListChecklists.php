<?php

namespace MediaWiki\Extension\Checklists\Rest;

use MediaWiki\Extension\Checklists\ChecklistManager;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\UserFactory;
use Wikimedia\ParamValidator\ParamValidator;

class ListChecklists extends SimpleHandler {

	/** @var ChecklistManager */
	private $manager;

	/** @var UserFactory */
	private $userFactory;

	/** @var TitleFactory */
	private $titleFactory;

	/**
	 * @param ChecklistManager $manager
	 * @param TitleFactory $titleFactory
	 * @param UserFactory $userFactory
	 */
	public function __construct( ChecklistManager $manager, TitleFactory $titleFactory, UserFactory $userFactory ) {
		$this->manager = $manager;
		$this->titleFactory = $titleFactory;
		$this->userFactory = $userFactory;
	}

	/**
	 * @return bool
	 */
	public function needsReadAccess() {
		return true;
	}

	/**
	 * @return \MediaWiki\Rest\Response|mixed
	 */
	public function execute() {
		$store = $this->manager->getStore();
		$params = $this->getValidatedParams();
		$page = $params['page'] ?? null;
		if ( $page ) {
			$title = $this->titleFactory->newFromText( $page );
			if ( !$title || !$title->exists() ) {
				return $this->getResponseFactory()->createJson( [ 'error' => 'Invalid page title' ], 400 );
			}
			$store->forPageIdentity( $title );
		}
		$user = $params['user'] ?? null;
		if ( $user ) {
			$user = $this->userFactory->newFromName( $user );
			if ( !$user || !$user->isRegistered() ) {
				return $this->getResponseFactory()->createJson( [ 'error' => 'Invalid user name' ], 400 );
			}
			$store->forUser( $user );
		}

		$items = $store->query();
		return $this->getResponseFactory()->createJson( $items );
	}

	/**
	 * @return array[]
	 */
	public function getParamSettings() {
		return [
			'page' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
			],
			'author' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
			],
		];
	}
}
