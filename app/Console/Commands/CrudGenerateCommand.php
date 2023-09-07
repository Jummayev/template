<?php

namespace App\Console\Commands;

use Brick\VarExporter\VarExporter;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\NoReturn;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CrudGenerateCommand extends Command
{
    private ?string $model_name;

    private ?string $controller_path;

    private ?string $table;

    private ?string $module;

    private bool $is_module = false;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:generate  {name? : Model (singular) for example User} {path? : Class (singular) for example User Api} {table? : Class (singular) for example users} {--module=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CRUD generate';

    public static array $integerTypes = [
        'smallint' => ['-32768', '32767'],
        'integer' => ['-2147483648', '2147483647'],
        'bigint' => ['-9223372036854775808', '9223372036854775807'],
    ];

    private array $notUse = ['id', 'created_at', 'updated_at', 'deleted_at', 'is_deleted'];

    private array $tableInfo = [];

    /**
     * Execute the console command.
     */
    #[NoReturn]
    public function handle(): void
    {
        $this->setParameters();
        $this->setTableInfo();
        dump($this->tableInfo);
        $this->generateModel();
        $this->generateRequest();
        $this->generateInterface();
        $this->generateRepository();
        $this->generateController();

        exec('./vendor/bin/pint');
        dd($this->model_name, $this->controller_path, $this->table, $this->module);
    }

    private function generateModel(): void
    {
        if ($this->is_module) {
            $namespace = "Modules\\$this->module\Entities";
        } else {
            $namespace = 'App\Models';
        }
        $attributes = array_keys($this->tableInfo);
        $casts = '';
        $usePath = "use Illuminate\Database\Eloquent\Relations\BelongsTo;\n";
        $useTraits = '';
        $relations = '';
        foreach ($this->tableInfo as $column_name => $attribute) {
            if (in_array($column_name, $this->notUse)) {
                continue;
            }

            if ($column_name == 'json' || $column_name == 'jsonb') {
                $casts .= "\n\t\t'$column_name' => 'array',";
            }
            /** create relation functions */
            if (! empty($attribute->foreign)) {
                $foreign = $attribute->foreign;
                $modelName = Str::studly(Str::singular($foreign['table']));
                $functionName = rtrim($column_name, '_id');
                if ($this->is_module and class_exists($namespace."\\$modelName")) {
                    $usePath .= "use $namespace\\$modelName;\n";
                }
                $relations .= "\n\tpublic function $functionName(): BelongsTo".
                    "\n\t{\n\t\treturn \$this->belongsTo($modelName::class);\n\t}\n";
            }
        }
        if (in_array('deleted_at', $attributes)) {
            $usePath .= "use Illuminate\Database\Eloquent\SoftDeletes;\n";
            $useTraits .= "\n\tuse SoftDeletes;\n";
        }

        $cast = "\n\tprotected \$casts = [".$casts."\n\t];\n";
        $modelTemplate = str_replace(
            [
                '{{namespace}}',
                '{{modelName}}',
                '{{fillable}}',
                '{{table}}',
                '{{casts}}',
                '{{path}}',
                '{{useTraits}}',
                '{{translatableFunctions}}',
                '{{relations}}',
            ],
            [
                $namespace,
                $this->model_name,
                json_encode($attributes, JSON_PRETTY_PRINT),
                $this->table,
                $cast,
                $usePath,
                $useTraits,
                '',
                $relations,
            ],
            $this->getStub('Model')
        );

        if (! empty($this->module) && $this->module !== 'no') {
            file_put_contents(base_path("/Modules/$this->module/Entities/$this->model_name.php"), $modelTemplate);
        } else {
            file_put_contents(app_path("/Models/$this->model_name.php"), $modelTemplate);
        }
    }

    private function setParameters(): void
    {
        $this->model_name = $this->argument('name');
        $this->controller_path = $this->argument('path');
        $this->table = $this->argument('table');
        $this->module = $this->option('module');
        if (empty($this->model_name)) {
            $this->model_name = text('Model (singular)', 'User', default: 'Post', required: true);
        }
        if (empty($this->controller_path)) {
            $this->controller_path = text('Controller Path', default: 'Api/v1');
        }
        if (empty($this->table)) {
            $this->table = text('Table ', default: Str::snake(Str::pluralStudly($this->model_name)), required: true);

        }
        if (empty($this->module)) {
            $confirm = confirm('Modulgami', false);
            if ($confirm == 'yes') {
                $modules = File::json(base_path('modules_statuses.json'));
                $modules = ['no' => true] + $modules;
                $this->module = select('Modules', array_keys($modules), 'no');
            }

        }
        if (! empty($this->module) && $this->module !== 'no') {
            $this->is_module = true;
        }
    }

    protected function getStub(string $type): string
    {
        return (string) file_get_contents(base_path("stubs/$type.stub"));
    }

    protected function generateController(): void
    {
        $paramName = lcfirst($this->model_name);
        if (! File::isDirectory(app_path('Http/Controllers/'.$this->controller_path))) {
            File::makeDirectory(app_path('Http/Controllers/'.$this->controller_path));
        }
        $fields = '';
        $response = '';
        $stub = '';
        foreach ($this->tableInfo as $key => $column) {
            $type = $column->type;
            switch ($type) {
                case 'text':
                    $type = 'string';
                    break;
                case 'bigint':
                    $type = 'integer';
                    break;
            }
            if (strtoupper('asd') === 'SWAGGER') {
                if (! in_array($key, $this->notUse)) {
                    $fields .= "\n\t *  \t\t\t@OA\Property(property='$key',type='$type'),";
                    $fields = str_replace("'", '"', $fields);
                    $stub = $this->getStub('ControllerSwagger');

                }
            } else {
                if (! in_array($key, $this->notUse)) {
                    $fields .= "     * @bodyParam $key $type\n";
                }
                $response .= "     *  \"$key\": \"$type\",\n";
                $stub = $this->getStub('Controller');

            }

        }
        $response = trim($response);

        if ($this->controller_path == '/') {
            $path = "/Http/Controllers/{$this->controller_path}Controller.php";
            $namespace = '';
        } else {
            $namespace = '\\'.str_replace('/', '\\', $this->controller_path);
            $path = "/Http/Controllers/{$this->controller_path}/{$this->model_name}Controller.php";
        }

        $controllerTemplate = str_replace(
            [
                '{{modelName}}',
                '{{modelNamePluralLowerCase}}',
                '{{modelNameSingularLowerCase}}',
                '{{fields}}',
                '{{namespace}}',
                '{{response}}',
                '{{paramName}}',
                '{{routeName}}',
            ],
            [
                $this->model_name,
                strtolower(Str::plural($this->model_name)),
                strtolower($this->model_name),
                $fields,
                $namespace,
                $response,
                $paramName,
                $this->table,
            ],
            $stub
        );

        file_put_contents(app_path($path), $controllerTemplate);
        //        $artisanCall = $documentation === 'SWAGGER' ? 'l5-swagger:generate' : 'scribe:generate';
        //        Artisan::call($artisanCall);
    }

    protected function generateRequest(): void
    {
        $createRules = [];
        $updateRules = [];
        foreach ($this->tableInfo as $column_name => $column) {
            if (in_array($column_name, $this->notUse)) {
                continue;
            }
            $columnRules = [];
            $columnRules[] = $column->is_nullable === 'YES' ? 'nullable' : 'required';

            $type = Str::of($column->type);
            switch (true) {
                case $type == 'boolean':
                    $columnRules[] = 'boolean';

                    break;
                case $type->contains('char'):
                    $columnRules[] = 'string';
                    $columnRules[] = 'max:'.$column->maximum_length;

                    break;
                case $type == 'text':
                    $columnRules[] = 'string';
                    break;
                case $type->contains('int'):
                    $columnRules[] = 'integer';
                    $columnRules[] = 'min:'.self::$integerTypes[$type->__toString()][0];
                    $columnRules[] = 'max:'.self::$integerTypes[$type->__toString()][1];

                    break;
                case $type->contains('double') ||
                $type->contains('decimal') ||
                $type->contains('numeric') ||
                $type->contains('real'):
                    // should we do more specific here?
                    // some kind of regex validation for double, double unsigned, double(8, 2), decimal etc...?
                    $columnRules[] = 'numeric';

                    break;
                case $type == 'date' || $type->contains('time '):
                    $columnRules[] = 'date';

                    break;
                case $type->contains('json'):
                    $columnRules[] = 'json';
                    break;
                default:
                    $columnRules[] = $column->type;
                    break;

            }
            if (! empty($column->foreign)) {
                $columnRules[] = 'exists:'.implode(',', $column->foreign);
            }
            $stringRules = implode('|', $columnRules);
            $createRules[$column_name] = $stringRules;
            $updateRules[$column_name] = Str::replace('required', 'nullable', $stringRules);

        }
        $this->createRequestFile($this->model_name.'/Store'.$this->model_name.'Request', $createRules);
        $this->createRequestFile($this->model_name.'/Update'.$this->model_name.'Request', $updateRules);
    }

    private function createRequestFile($name, array $rules = []): void
    {
        Artisan::call('make:request', [
            'name' => $name,
            '--force' => true,
        ]);

        $output = trim(Artisan::output());

        preg_match('/\[(.*?)]/', $output, $matches);

        // The original $file we passed to the command may have changed on creation validation inside the command.
        // We take the actual path which was used to create the file!
        $actualFile = $matches[1] ?? null;

        if ($actualFile) {
            try {
                $rules = VarExporter::export($rules, VarExporter::INLINE_SCALAR_LIST);
                $fileContent = File::get($actualFile);
                // Add spaces to indent the array in the request class file.
                $rulesFormatted = str_replace("\n", "\n        ", $rules);
                $pattern = '/(public function rules\(\): array\n\s*{\n\s*return )\[.*](;)/s';
                $replaceContent = preg_replace($pattern, '$1'.$rulesFormatted.'$2', $fileContent);
                File::put($actualFile, $replaceContent);
            } catch (Exception $exception) {
                $this->error($exception->getMessage());
            }
        }

        if (Str::startsWith($output, 'INFO')) {
            $this->info($output);
        } else {
            $this->error($output);
        }
    }

    public function getColumnInfo($table, $column)
    {
        $info = DB::select("SELECT is_nullable, character_maximum_length
                                    FROM information_schema.columns
                                    WHERE table_name = '$table'
                                    AND column_name='$column';");

        return $info[0];
    }

    public function setTableInfo(): void
    {
        $databaseName = config('database.connections.pgsql.database');
        $tableColumns = collect(DB::select(
            '
            SELECT
                column_name as name,
                data_type as type,
                character_maximum_length as maximum_length,
                is_nullable,
                column_default as default
                FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = ?',
            [$this->table]
        ))->keyBy('name')->toArray();

        $foreignKeys = DB::select("
            SELECT
                kcu.column_name,
                ccu.table_name AS foreign_table_name,
                ccu.column_name AS foreign_column_name
            FROM
                information_schema.table_constraints AS tc
                JOIN information_schema.key_column_usage AS kcu
                  ON tc.constraint_name = kcu.constraint_name
                  AND tc.table_schema = kcu.table_schema
                JOIN information_schema.constraint_column_usage AS ccu
                  ON ccu.constraint_name = tc.constraint_name
                  AND ccu.table_schema = tc.table_schema
            WHERE tc.constraint_type = 'FOREIGN KEY' AND tc.table_name=? AND tc.table_catalog=?
        ", [$this->table, $databaseName]);

        foreach ($foreignKeys as $foreignKey) {
            $tableColumns[$foreignKey->column_name]->foreign = [
                'table' => $foreignKey->foreign_table_name,
                'id' => $foreignKey->foreign_column_name,
            ];
        }

        foreach ($tableColumns as $key => $column) {
            $type = Str::of($column->type);
            $type = match (true) {
                $type == 'boolean' => 'boolean',
                $type == 'text', $type->contains('char') => 'string',
                $type->contains('int') => 'integer',
                $type->contains('double'),
                $type->contains('decimal'),
                $type->contains('numeric'),
                $type->contains('real') => 'numeric',
                $type == 'date', $type->contains('time ') => 'date',
                $type->contains('json') => 'json',
                default => $column->type,
            };
            $tableColumns[$key]->type = $type;
        }

        $this->tableInfo = $tableColumns;
    }

    protected function generateInterface(): void
    {
        $interfaceTemplate = str_replace(
            [
                '{{modelName}}',
                '{{paramName}}',
            ],
            [
                $this->model_name,
                Str::snake($this->model_name),
            ],

            $this->getStub('Interface')
        );
        if (! File::isDirectory(app_path('Http/Interfaces'))) {
            File::makeDirectory(app_path('Http/Interfaces'));
        }
        file_put_contents(app_path("Http/Interfaces/{$this->model_name}Interface.php"), $interfaceTemplate);
    }

    public function generateRepository()
    {

        if (! File::isDirectory(app_path('Http/Repositories/'))) {
            File::makeDirectory(app_path('Http/Repositories/'));
        }
        //        $attributes = Schema::getColumnListing($this->table);
        //        if (in_array('lang_hash', $attributes)) {
        //            $stub = file_get_contents(resource_path('stubs/RepositoryWithTranslation.stub'));
        //        } else {
        $stub = $this->getStub('Repository');
        //        }
        $repositoryTemplate = str_replace(['{{modelName}}', '{{paramName}}'], [$this->model_name, lcfirst($this->model_name)], $stub);
        file_put_contents(app_path("Http/Repositories/{$this->model_name}Repository.php"), $repositoryTemplate);
    }
}
