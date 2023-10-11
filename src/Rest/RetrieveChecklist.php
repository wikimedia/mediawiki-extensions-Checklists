<?php

namespace MediaWiki\Extension\Checklists\Rest;

use MediaWiki\Extension\Checklists\ChecklistManager;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

class RetrieveChecklist extends SimpleHandler {

	/** @var ChecklistManager */
	private $manager;

	/**
	 * @param ChecklistManager $manager
	 */
	public function __construct( ChecklistManager $manager ) {
		$this->manager = $manager;
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
		$items = $store->id( $params['id'] )->query();
		if ( !$items ) {
			return $this->getResponseFactory()->createJson( [ 'error' => 'Checklist not found' ], 404 );
		}
		return $this->getResponseFactory()->createJson( $items[0] );
	}

	/**
	 * @return array[]
	 */
	public function getParamSettings() {
		return [
			'id' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}
}
