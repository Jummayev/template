<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
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
    protected $signature = 'crud:generate {--name= : Model (singular) for example User} {--path : Class (singular) for example User Api} {--table : Class (singular) for example users} {--module=}';

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

        $this->generateModel($model_name, $table);

        dd($model_name, $path, $table, $module);
    }

    private function generateModel(string $model_name, string $table): void
    {
        $attributes = Schema::getColumnListing($table);

        $fields = '';
        $casts = '';
        $rules = '';
        $columns = '';
        $path = '';
        $useTraits = '';
        foreach ($attributes as $attribute) {
            if ($attribute != 'id') {
                $fields .= "\n\t\t\t'$attribute', ";
                $type = Schema::getColumnType($table, $attribute);
                $rules .= match ($type) {
                    'text' => "\n\t\t\t'$attribute' => 'string|nullable',",
                    'bigint' => "\n\t\t\t'$attribute' => 'integer|nullable',",
                    'json', 'jsonb' => "\n\t\t\t'$attribute' => 'array|nullable',",
                    'datetime' => "\n\t\t\t'$attribute' => 'date|nullable',",
                    default => "\n\t\t\t'$attribute' => '$type|nullable',",
                };
                $columns .= match ($type) {
                    'text', 'datetime' => "\n * @property string $attribute",
                    'bigint' => "\n * @property integer $attribute",
                    'json', 'jsonb' => "\n * @property array $attribute",
                    default => "\n * @property $type $attribute",
                };
                if ($type == 'json' || $type == 'jsonb') {
                    $casts .= "\n\t\t'$attribute' => 'array',";
                }
            }
        }
        if (in_array('deleted_at', $attributes)) {
            $path .= "use Illuminate\Database\Eloquent\SoftDeletes;\n";
            $useTraits .= "\n\tuse SoftDeletes;";
        }

        $cast = "\n\tprotected \$casts = [".$casts."\n\t];\n";
        $modelTemplate = str_replace(
            [
                '{{modelName}}',
                '{{fillable}}',
                '{{table}}',
                '{{casts}}',
                '{{path}}',
                '{{useTraits}}',
                '{{rules}}',
                '{{columns}}',
                '{{translatableFunctions}}',
                '{{relations}}',
            ],
            [
                $model_name,
                $fields,
                $table,
                $cast,
                $path,
                $useTraits,
                $rules,
                $columns,
                '',
                '',
            ],
            $this->getStub('Model')
        );

        file_put_contents(app_path("/Models/$model_name.php"), $modelTemplate);
    }

    /**
     * @return  array<string>
     */
    private function getParameters(): array
    {
        $model_name = $this->option('name');
        $path = $this->option('path');
        $table = $this->option('table');
        $module = $this->option('module');
        if (empty($model_name)) {
            $model_name = text('Model (singular)', 'User', required: true);
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
                $modules = ['No model' => true] + $modules;
                $module = select('Modules', array_keys($modules), 'No model');
            }

        }

        return [$model_name, $path, $table, $module];
    }

    protected function getStub(string $type): string
    {
        return (string) file_get_contents(base_path("stubs/$type.stub"));
    }
}
