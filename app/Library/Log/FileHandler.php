<?php

namespace App\Library\Log;

use App\Constant\GlobalContext;
use App\Library\Context\Context;
use Illuminate\Support\Str;
use Monolog\Handler\AbstractProcessingHandler;

/**
 * 日志自定义处理
 */
class LogHandle extends AbstractProcessingHandler
{
    /**
     * Context日志记录KEY
     */
    private const LOG_RECORD = 'log_record';

    /**
     * Context日志作用域
     */
    private const LOG_SCOPE = 'galaxy_scope';

    /**
     * @inheritDoc
     */
    protected function write(array $record): void
    {
        $key       = md5($record['formatted']);
        $logRecord = Context::get(self::LOG_RECORD, [], self::LOG_SCOPE);
        if (!in_array($key, $logRecord)) {
            $logPath = $this->getLogPath($record['level_name']);
            file_put_contents($logPath, $this->format($record) . "\n", FILE_APPEND);
            Context::multiSet([self::LOG_RECORD . ".[]" => $key], self::LOG_SCOPE);
        }
    }

    /**
     * 获取存取路径
     * @param string $level
     * @return string
     */
    private function getLogPath(string $level)
    {
        $level  = strtolower($level);
        $module = strtolower(Context::get(GlobalContext::DOMAIN, 'default'));
        $key    = "log_paths.{$module}#{$level}";
        if (!Context::has($key, self::LOG_SCOPE)) {
            $logRootDir = dirname(config("logging.channels.{$level}.path")) ?: storage_path('logs'); // 日志根目录
            $logStorDir = sprintf("%s/%s/%s", $logRootDir, $module, date('Ymd'));                    // 实际存储日志的目录
            if (!is_dir($logStorDir) && !mkdir($logStorDir, 0755, true)) {
                return "{$logRootDir}/{$level}.log";
            }
            Context::set($key, $logStorDir, self::LOG_SCOPE);
        }

        return Context::get($key, [], self::LOG_SCOPE) . "/{$level}.log";
    }

    /**
     * 格式化日志
     * @param array $record 日志信息
     * @return string
     */
    private function format(array $record): string
    {
        $isCli   = strpos(php_sapi_name(), 'cli') === false;
        $context = [
            'req_id'    => $record['context']['req_id'] ?? (string) Str::uuid(),
            'level'     => strtolower($record['level_name']),
            'message'   => $record['message'],
            'context'   => array_diff_key($record['context'], ['exception', 'req_id']),
            'host'      => request()->getHost(),
            'client'    => request()->getClientIps(),
            'method'    => request()->getMethod(),
            'uri'       => $isCli ? request()->getPathInfo() : implode(' ', $_SERVER['argv'] ?? []),
            'params'    => collect(request()->input())->except('s'),
            'dispatch'  => sprintf("%s#%s@%s",
                strtolower(Context::get(GlobalContext::DOMAIN)),
                Context::get(GlobalContext::CONTROLLER),
                Context::get(GlobalContext::ACTION)
            ),
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        if (isset($record['context']['exception']) && $record['context']['exception'] instanceof \Throwable) {
            $error            = $record['context']['exception'];
            $first            = collect($error->getTrace())->first();
            $context['error'] = [
                'code'     => $error->getCode(),
                'message'  => $error->getMessage(),
                'position' => sprintf("%s on line %d", $error->getFile(), $error->getLine()),
                'first'    => isset($first['file']) ? sprintf("%s on line %d", $first['file'], $first['line']) : null,
            ];

            unset($record['context']['exception']);
            unset($record['context']['req_id']);
            $context['context'] = empty($record['context']) ? (object) [] : $record['context'];
        }

        return json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
