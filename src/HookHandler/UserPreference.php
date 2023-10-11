<?php

namespace MediaWiki\Extension\Checklists\HookHandler;

use MediaWiki\Preferences\Hook\GetPreferencesHook;

class UserPreference implements GetPreferencesHook {

	/**
	 * @param User $user
	 * @param array &$defaultPreferences
	 */
	public function onGetPreferences( $user, &$defaultPreferences ) {
		$defaultPreferences['checklists-hide-revision-dlg'] = [
			'type' => 'api',
			'default' => '0',
		];
	}
}
