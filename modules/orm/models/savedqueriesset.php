<?php

require_once( dirname(__FILE__).'/base/basesavedqueriesset.php' );

/**
 * Autogenerated class SavedQueriesSet_Model
 *
 * @todo: documentation
 */
class SavedQueriesSet_Model extends BaseSavedQueriesSet_Model {
	/**
	 * apply some extra filters to match for the authentication.
	 */
	protected function get_auth_filter() {
		$auth = Auth::instance();
		$username = $auth->get_user()->username;

		$auth_filter = new LivestatusFilterOr();
		$auth_filter->add(new LivestatusFilterMatch('username', $username));
		$auth_filter->add(new LivestatusFilterMatch('username', '-'));

		$result_filter = new LivestatusFilterAnd();
		$result_filter->add($this->filter);
		$result_filter->add($auth_filter);
		return $result_filter;
	}
}
