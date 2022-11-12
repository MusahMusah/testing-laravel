<?php

namespace App\Http\Resources\Ghost;

use Illuminate\Http\Resources\Json\JsonResource;

class EmptyResource extends JsonResource
{
    public function toArray($request)
    {
        // return empty resource
        return [];
    }
}
