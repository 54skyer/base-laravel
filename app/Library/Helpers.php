<?php

namespace App\Library;

/**
 * 工具类，必须同时满足以下条件
 * 1、通用且与业务无关
 * 2、不支持迁移，主要是依赖框架特性。所以这里与Functions的区分是，Helpers可以依赖Functions，但反过来不行
 */
class Helpers
{
	public static function config($a) {
		return config("database.connections");
	}
}
