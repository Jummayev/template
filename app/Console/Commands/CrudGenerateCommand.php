<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CrudGenerateCommand extends Command
{
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

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        [$model_name, $path, $table, $module] = $this->getParameters();

        $this->generateModel($model_name, $table, $module);
        $paramName = lcfirst($model_name);
        $notUse = ['id', 'created_at', 'updated_at', 'deleted_at', 'is_deleted'];

        $this->controller($model_name, $path, $table, $paramName, $notUse, 'sadsd');

        exec('./vendor/bin/pint');
        dd($model_name, $path, $table, $module);
    }

    private function generateModel(string $model_name, string $table, string $module = null): void
    {
        $attributes = Schema::getColumnListing($table);

        if (! empty($module) && $module !== 'no') {
            $namespace = "Modules\\$module\Entities;";
        } else {
            $namespace = 'App\Models;';
        }
        $fillable = '';
        $casts = '';
        $usePath = "use Illuminate\Database\Eloquent\Relations\BelongsTo;\n";
        $useTraits = '';
        $relations = '';
        $files = ['poster', 'photo', 'icon', 'file', 'video'];
        foreach ($attributes as $attribute) {
            if ($attribute != 'id') {
                $fillable .= "\n\t\t'$attribute', ";
                $type = Schema::getColumnType($table, $attribute);
                if ($type == 'json' || $type == 'jsonb') {
                    $casts .= "\n\t\t'$attribute' => 'array',";
                }
            }
            /** create relation functions */
            if (str_contains($attribute, '_id')) {
                $modelName = rtrim($attribute, '_id');
                $modelNameToUpCase = ucfirst($modelName);
                if (in_array($modelName, $files)) {
                    $usePath .= "use Modules\FileManager\Entities\File;\n";
                    $relations .= "\n\tpublic function $modelName(): BelongsTo".
                        "\n\t{\n\t\treturn \$this->belongsTo(Files::class);\n\t}\n";
                } elseif (class_exists("App\Models\\$modelNameToUpCase")) {
                    $relations .= "\n\tpublic function $modelName(): BelongsTo".
                        "\n\t{\n\t\treturn \$this->belongsTo($modelNameToUpCase::class);\n\t}\n";
                }
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
                $model_name,
                $fillable,
                $table,
                $cast,
                $usePath,
                $useTraits,
                '',
                $relations,
            ],
            $this->getStub('Model')
        );

        if (! empty($module) && $module !== 'no') {
            file_put_contents(base_path("/Modules/$module/Entities/$model_name.php"), $modelTemplate);
        } else {
            file_put_contents(app_path("/Models/$model_name.php"), $modelTemplate);
        }
    }

    /**
     * @return  array<string>
     */
    private function getParameters(): array
    {
        $model_name = $this->argument('name');
        $path = $this->argument('path');
        $table = $this->argument('table');
        $module = $this->option('module');
        if (empty($model_name)) {
            $model_name = text('Model (singular)', 'User', required: true, default: 'Post');
        }
        if (empty($path)) {
            $path = text('Path ', default: 'Api/v1');
        }
        if (empty($table)) {
            $table = text('Table ', default: Str::snake(Str::pluralStudly($model_name)), required: true);
        }
        if (empty($module)) {
            $confirm = confirm('Modulgami', false);
            if ($confirm == 'yes') {
                $modules = File::json(base_path('modules_statuses.json'));
                $modules = ['no' => true] + $modules;
                $module = select('Modules', array_keys($modules), 'no');
            }

        }

        return [$model_name, $path, $table, $module];
    }

    protected function getStub(string $type): string
    {
        return (string) file_get_contents(base_path("stubs/$type.stub"));
    }

    protected function controller($name, $path, $tableName, $paramName, $notUse, $documentation)
    {
        $attributes = Schema::getColumnListing($tableName);
        $fields = '';
        $response = '';
        $stub = '';
        foreach ($attributes as $attribute) {
            $type = Schema::getColumnType($tableName, $attribute);
            switch ($type) {
                case 'text':
                    $type = 'string';
                    break;
                case 'bigint':
                    $type = 'integer';
                    break;
            }
            if (strtoupper($documentation) === 'SWAGGER') {
                if (! in_array($attribute, $notUse)) {
                    $fields .= "\n\t *  \t\t\t@OA\Property(property='$attribute',type='$type'),";
                    $fields = str_replace("'", '"', $fields);
                    $stub = $this->getStub('ControllerSwagger');

                }
            } else {
                $fields .= "     * @bodyParam {$attribute} {$type}\n";
                $response .= "     *  \"{$attribute}\": \"{$type}\",\n";
                $stub = $this->getStub('Controller');

            }

        }

        if ($path == '/') {
            $path = "/Http/Controllers/{$name}Controller.php";
            $namespace = '';
        } else {
            $namespace = '\\'.str_replace('/', '\\', $path);
            $path = "/Http/Controllers/{$path}/{$name}Controller.php";
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
                $name,
                strtolower(Str::plural($name)),
                strtolower($name),
                $fields,
                $namespace,
                $response,
                $paramName,
                $tableName,

            ],
            $stub
        );

        file_put_contents(app_path($path), $controllerTemplate);
        //        $artisanCall = $documentation === 'SWAGGER' ? 'l5-swagger:generate' : 'scribe:generate';
        //        Artisan::call($artisanCall);
    }
}
