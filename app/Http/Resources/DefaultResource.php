<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DefaultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        $appends = explode(',', $request->get('append'));
        if ($request->filled('append')) {
            foreach ($appends as $append) {
                $data[$append] = $this->$append;
            }
        }

        return $data;
    }
}
