<?php

class PadLib_Dataql_Fields {
	public $fields = array();

	/**
	 * @extends 用于继承其他分组的所有字段,例如: @extends(default)
	 * 属性字段别名,例如: category.name as catName
	 * 属性字段引用,例如: goods(goods.default) as goodsInfo
	 * 属性字段数组,例如: goodsList([goods.default]) as goodsList
	 * 方法调用,例如: getSaleCount[100,20] as saleCount
	 * 方法调用(结果是属性),例如: getUser[__member_id](user.default) as useDetail
	 * 方法调用(结果是属性数组),例如: getUserList[__member_id]([user.default]) as useList
	 *
	 * @return $this
	 */
	public function push () {
		foreach (func_get_args() as $field) {
			$this->fields[] = $field;
		}
		return $this;
	}

	public function pushCallback ($field, $cbk) {
		$this->fields[] = new PadLib_Dataql_Fields_Callback($field, $cbk);
		return $this;
	}

	public function getData () {
		return $this->fields;
	}
}

class PadLib_Dataql_Fields_Callback {
	public $field;
	public $callback;

	public function __construct($field, $cbk) {
		$this->field = $field;
		$this->callback = $cbk;
	}
}

