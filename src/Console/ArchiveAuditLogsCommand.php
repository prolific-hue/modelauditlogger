<?php

namespace ProlificHue\ModelAuditLogger\Console;

use Illuminate\Console\Command;
use ProlificHue\ModelAuditLogger\Helpers;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ArchiveAuditLogsCommand extends Command
{
	/**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit_logger:archive {--table= : archiving only table logs, comma separated, all for all table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will archive all audit logs.';

    private $tables = [];
    private $isAll = false;

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
     * @return mixed
     */
    public function handle()
    {
    	Helpers::driverCheck();
    
    	$this->isAll = $this->option('table') == 'all' || empty($this->option('table'));

    	if(!$this->isAll)
    		$this->tables = explode(',', $this->option('table'));

        Helpers::isFileDriver() ? $this->bootFile() : $this->bootDB();
    }

    private function bootDB()
    {
    	$model = config('modelauditlogger.model');
    	$model = new $model;

    	if(!$this->isAll)
    		$model = $model->whereIn('table', $this->tables);

    	$columns = ['id', 'table', 'model_id', 'model_type', 'user_id', 'user_type', 'ip_address', 'payload', 'created_at'];

    	$model = $model->selectRaw('? as archived_at', [Carbon::now()->toDateTimeString()])
        ->addSelect($columns);

        array_unshift($columns, 'archived_at');
        
    	DB::table(config('modelauditlogger.drivers.database.archive_table'))->insertUsing($columns, $model);

    	$model->delete();
    }

    private function bootFile()
    {
    	$now = Carbon::now();
    	$filesettings = config('modelauditlogger.drivers.file');
    	$storage = Storage::disk( $filesettings['disk'] );
    	$archive_path = $filesettings['archive_path'] . DIRECTORY_SEPARATOR . $now->toDateString();

    	$path = $filesettings['path'] . DIRECTORY_SEPARATOR;

        $available_tables = array_map(function($item) use($path){
            return str_replace($path, '', $item);
        }, $storage->directories($path)); 

    	if($this->isAll){
            $tables = $available_tables;
    	}else{
            $tables = array_filter($this->tables, function($item) use($available_tables){
                return in_array($item, $available_tables);
            });
    	}

        // $this->info( json_encode($tables));
        // return;
        foreach ($tables as $table) {
            $this->archiveFileTable($storage, $table, $path, $archive_path);
        }
    }

    private function archiveFileTable($storage, $table, $path, $archive_path)
    {

        $tmps = $storage->files($path.$table);
        $files = [];
        foreach ($tmps as $item) {
            $files[str_replace($path.$table.DIRECTORY_SEPARATOR, '', $item)] = $item;
        }
        
        $tmps = $storage->files($archive_path.$table);
        $archive_files = [];
        foreach ($tmps as $item) {
            $archive_files[str_replace($archive_path.$table.DIRECTORY_SEPARATOR, '', $item)] = $item;
        }

        unset($tmps);

        $this->info(json_encode($files));
        $this->info(json_encode($archive_files));
        // return;

        foreach ($files as $fileName => $filePath) {
            if($storage->getSize($filePath) < 0)
                continue;
            $data = $storage->get($filePath);

            /* merge old archived files with new one */
            if(isset($archive_files[$fileName]))
            {
                if($storage->getSize($archive_files[$fileName]) > 0)
                    $data = $data.',';

                $storage->prepend($archive_files[$fileName], $data); 
            }
            else
            {
                $archive_abs_path = $archive_path . DIRECTORY_SEPARATOR . $table . DIRECTORY_SEPARATOR . $fileName;
                $storage->put($archive_abs_path, $data);
            }

            $storage->put($filePath, ''); // emptied
        }
        $this->info('logs archived in ' . $archive_path);
    }
}