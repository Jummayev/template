<?php

namespace App\Traits;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\QueryBuilder;

trait LangHashTranslatable
{
    public function setTranslation(string $value, string $attr): void
    {
        $this->attributes[$attr] = $value;
        if (in_array('slug', $this->attributes)) {
            $this->attributes['slug'] = Str::slug($value.'-'.rand(1, 100));
        }

        if (! array_key_exists('id', $this->attributes)) {
            $this->attributes['lang_hash'] = empty($this->attributes['lang_hash'])
                ? Str::random(32)
                : $this->attributes['lang_hash'];

            $this->attributes['lang'] = empty($this->attributes['lang'])
                ? Langs::getLangId(Lang::getLocale())
                : $this->attributes['lang'];
        }
    }

    public function getTranslation($modelClass): array
    {
        $data = [];
        $models = QueryBuilder::for(get_class($modelClass))
            ->where('id', '<>', $this->attributes['id'])
            ->where('lang_hash', $this->attributes['lang_hash'])
            ->get();

        if (count($models) > 0) {
            foreach ($models as $model) {
                $lang = Langs::getLangCode($model->lang);
                if (! $model->slug) {
                    $data[] = [
                        'id' => $model->id,
                        'lang' => $lang,
                    ];
                } else {
                    $data[] = [
                        'id' => $model->id,
                        'slug' => $model->slug,
                        'lang' => $lang,
                    ];
                }
            }
        }

        return $data;
    }
}
