<?php

namespace TreasureChest\Traits\Laravel\Console\CodeCreator;

use Illuminate\Console\Command;

class CodeCreator extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:code_creator:laravel:ddd  {action} {namespace} {domain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ddd模式，在laravel框架下，代码生成器';

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
        try {
            $action    = $this->argument('action');
            $namespace = $this->argument('namespace');
            $domain    = $this->argument('domain');
            switch ($action) {
                case 'domain':
                    $this->domain($namespace, $domain);
                    break;
                case 'route':
                    $this->route($namespace, $domain);
                    break;
                case 'controller':
                    $this->controller($namespace, $domain);
                    break;
                case 'service':
                    $this->service($namespace, $domain);
                    break;
                case 'logic':
                    $this->logic($namespace, $domain);
                    break;
                case 'repository':
                    $this->repository($namespace, $domain);
                    break;
                default:
                    $help = <<<EOS
                
                Usage:
                  [%s] php artisan command:code_creator:laravel:ddd {action} {namespace} {domain}
                  
                EOS;
                    $this->info(sprintf($help, PHP_BINARY));

                    return 0;
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

            return 1;
        }
    }
}