<?php

namespace App\Library\Log;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SQL日志
 */
class SqlLog
{
    /**
     * 百分号
     */
    const PERCENT_CHAR = '^#percent_char#^';

    /**
     * 启动入口
     */
    public static function boot()
    {
        DB::listen(function (QueryExecuted $query) {
            if (!env('DB_DEBUG', false)){
	            Log::channel('sql')->info("[{$query->time}ms]" . self::getSql($query));
            }
        });
    }

    /**
     * 获取SQL
     * @param QueryExecuted $query
     * @return string
     */
    private static function getSql(QueryExecuted $query): string
    {
        $binds  = [];
        $sqlTpl = str_replace('?', '%s', str_replace('%', self::PERCENT_CHAR, $query->sql));
        foreach ($query->bindings as $key => $value) {
            $value = is_int($value) || is_float($value) ? $value : "'{$value}'";
            if (is_int($key)) {
                //['abc', 'efg']模式
                $binds[] = $value;
            } else {
                //['a' => 'abc']模式
                $sqlTpl = str_replace(":{$key}", $value, $sqlTpl);
            }
        }

        if (count($binds) > 0) {
            $sqlTpl = vsprintf($sqlTpl, $binds);
        }
        return str_replace(self::PERCENT_CHAR, '%', $sqlTpl);
    }
}
