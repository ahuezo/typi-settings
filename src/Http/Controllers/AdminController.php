<?php
namespace TypiCMS\Modules\Settings\Http\Controllers;

use Cache;
use Config;
use Input;
use McCool\DatabaseBackup\BackupProcedure;
use McCool\DatabaseBackup\Dumpers\MysqlDumper;
use McCool\DatabaseBackup\Processors\ShellProcessor;
use Notification;
use Redirect;
use Response;
use Symfony\Component\Process\Process;
use TypiCMS\Http\Controllers\BaseAdminController;
use TypiCMS\Modules\Settings\Http\Requests\FormRequest;
use TypiCMS\Modules\Settings\Repositories\SettingInterface;
use View;

class AdminController extends BaseAdminController
{

    public function __construct(SettingInterface $setting)
    {
        parent::__construct($setting);
    }

    /**
     * List models
     * GET /admin/model
     */
    public function index()
    {
        $data = $this->repository->getAll();
        return view('settings::admin.index')
            ->withData($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        $data = Input::all();

        // add checkboxes data
        $data['lang_chooser'] = Input::get('lang_chooser', 0);
        $data['auth_public']  = Input::get('auth_public', 0);
        $data['register']     = Input::get('register', 0);
        foreach (Config::get('translatable.locales') as $locale) {
            $data[$locale]['status'] = Input::get($locale.'.status', 0);
        }

        $this->repository->store($data);

        return Redirect::route('admin.settings.index');

    }

    /**
     * Clear app cache
     *
     * @return redirect
     */
    public function clearCache()
    {
        Cache::flush();
        Notification::success(trans('settings::global.Cache cleared') . '.');
        return Redirect::route('admin.settings.index');
    }

    /**
     * Backup DB
     *
     * @return File download
     */
    public function backup()
    {
        // DB info
        $host = Config::get('database.connections.mysql.host');
        $username = Config::get('database.connections.mysql.username');
        $password = Config::get('database.connections.mysql.password');
        $database = Config::get('database.connections.mysql.database');

        // SQL file
        $file = storage_path().'/backup/'.$database.'.sql';

        // Export
        $shellProcessor = new ShellProcessor(new Process(''));
        $dumper = new MysqlDumper($shellProcessor, $host, 3306, $username, $password, $database, $file);
        $backup = new BackupProcedure($dumper);
        $backup->backup();

        // DL File
        return Response::download($file);
    }
}
