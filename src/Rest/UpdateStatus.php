<?php

namespace MediaWiki\Extension\Checklists\Rest;

use MediaWiki\Extension\Checklists\ChecklistManager;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MWException;
use Wikimedia\ParamValidator\ParamValidator;

class UpdateStatus extends SimpleHandler {

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
	public function needsWriteAccess() {
		return true;
	}

	/**
	 * @return Response
	 * @throws MWException
	 */
	public function execute() {
		$user = \RequestContext::getMain()->getUser();
		$params = $this->getValidatedParams();
		$body = $this->getValidatedBody();
		$store = $this->manager->getStore();
		$items = $store->id( $params['id'] )->query();
		if ( !$items ) {
			return $this->getResponseFactory()->createJson( [ 'error' => 'Checklist item not found' ], 404 );
		}
		$item = $items[0];
		$rev = $this->manager->setStatusForChecklistItem( $item, $body[ 'value' ], $user );
		if ( !$rev ) {
			return $this->getResponseFactory()->createJson( [ 'error' => 'Failed to update checklist' ], 500 );
		}
		return $this->getResponseFactory()->createJson( [ 'rev' => $rev->getId() ] );
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

	/**
	 * @return array[]
	 */
	public function getBodyParamSettings(): array {
		return [
			'value' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => ''
			],
		];
	}

	public function getSupportedRequestTypes(): array {
		return [
			'application/x-www-form-urlencoded',
			'multipart/form-data',
			'application/json'
		];
	}
}
