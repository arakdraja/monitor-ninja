<?php


/**
 * The univese of a objects of a given type in livestatus
 */
class ContactPool_Model extends BaseContactPool_Model {
	/**
	 * Fetch contact information
	 *
	 * @return Contact_Model|bool;
	 */
	public static function get_current_contact()
	{
		$username = Auth::instance()->get_user()->get_username();

		$set = ContactPool_Model::all();
		$set = $set->reduce_by('name', $username, '=');


		$result = iterator_to_array($set,false);

		if(count($result) != 1)
			return false;

		return $result[0];
	}
}
