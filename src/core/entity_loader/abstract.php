<?php

/**
 * Created by PhpStorm.
 * User: macmini
 * Date: 16/2/25
 * Time: 下午3:55
 */
class EntityLoader_Abstract extends PadOrmLoader {
	public function getXLSFromList($pres, $lists) {
		if (empty($lists)) {
			throw new PadBizException('没有数据, 无法导出');
		}

		$keys = array_keys($pres);//获取表头的键名
		$content = "";
		$content .= "<table border='1'><tr>";
		//输出表头键值
		foreach ($pres as $_pre) {
			$content .= "<td>$_pre</td>";
		}
		$content .= "</tr>";
		foreach ($lists as $_list) {
			$content .= "<tr>";
			foreach ($keys as $key) {
				$content .= "<td style='vnd.ms-excel.numberformat:@'>" . $_list[$key] . "</td>"; //style样式将导出的内容都设置为文本格式 输出对应键名的键值 即内容
			}
			$content .= "</tr>";
		}
		$content .= "</table>";
		return $content;
	}
}


