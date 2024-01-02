<?php

namespace App\Console\Commands\System;

use Doctrine\DBAL\Schema\Column;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ModelGenerator extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:model-generator {namespace} {table} {--connection=mysql}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '模型文件生成器';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $namespace = $this->argument("namespace", "");
        if (empty($namespace)) {
            $this->error("未指定命名空间");
        }
        $namespaceArray = $namespaceArrayOrigin = explode(".", $namespace);

        $table = $this->argument("table", "");
        if (empty($table)) {
            $this->error("未指定表名");
        }

        $connection = $this->option("connection", "mysql");

        try {
            $connectionConfig = config("database.connections.{$connection}");

            $schemaManager = DB::connection($connection)
                               ->getDoctrineSchemaManager();

            $tableSchema = $schemaManager->introspectTable($table);

            $tableDetails = $tableSchema->getColumns($table);

            $fieldCommentList = [];
            $fieldList        = [];
            /**
             * @var string $fieldName
             * @var Column $fieldDetail
             */
            foreach ($tableDetails as $fieldName => $fieldDetail) {
                $fieldList[]        = $fieldName;
                $fieldCommentList[] = sprintf(" * @property %s     %s     %s",
                    $fieldDetail->getType()->getName(),
                    $fieldName,
                    $fieldDetail->getComment()
                );
            }
            $tableAnnotation      = " * ".$tableSchema->getComment();
            $tableFieldAnnotation = implode(PHP_EOL, $fieldCommentList);
            $fieldList            = implode("',".PHP_EOL."'", $fieldList);

            $tableClassName = Str::studly($table);
            # 约定命名空间的第一个成员映射项目根目录下的目录名称，比如["app" => "App", "database" => "Database", "test" => "Test"];
            $namespaceArrayFirstItem = array_shift($namespaceArray);
            $namespaceArrayFirstItemMapTo
                                     = Str::camel($namespaceArrayFirstItem);

            array_unshift($namespaceArray, $namespaceArrayFirstItemMapTo);
            $modelFieldPath = base_path().DIRECTORY_SEPARATOR
                              .implode(DIRECTORY_SEPARATOR, $namespaceArray)
                              .DIRECTORY_SEPARATOR.$tableClassName.".php";
            if (file_exists($modelFieldPath)) {
                # 不存在直接创建文件，文件已存在，就更新注释和fillable

            } else {
                $modelFileContent
                    = view("System.model", [
                    "namespace"            => implode('\\', $namespaceArrayOrigin),
                    "tableAnnotation"      => $tableAnnotation,
                    "tableFieldAnnotation" => $tableFieldAnnotation,
                    "tableClassName"       => $tableClassName,
                    "tableName"            => $table,
                    "mysqlConnectionName"  => $connection,
                    "fieldList"            => $fieldList,
                ])->render();

                file_put_contents($modelFieldPath, $modelFileContent);
            }
        } catch (Throwable $e) {
            $error = sprintf(
                'Uncaught exception "%s"([%d]%s) at %s:%s, %s%s',
                get_class($e),
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                PHP_EOL,
                $e->getTraceAsString()
            );
            $this->error($error);
        }
    }
}