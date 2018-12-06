<?php

class PadLib_DevData {

	static public function create () {
		$return = array();

		$creater = new stdClass();
		return $creater->res($return);
	}

	static public function createList () {

	}

	static public function createPageList () {

	}

	/**
	 * @return PadLib_DevData_Type
	 */
	static public function type () {
		return new PadLib_DevData_Type();
	}

	public function __construct() {
	}

	public function sample () {
		$creater = new self();

		$ddType = PadLib_DevData::type();
		PadLib_DevData::create(array(
			'id' => $ddType->number(1, 20),
			'title' => $ddType->string(1, 20),
			'create_time' => $ddType->timestrap(),
		));
	}
}



