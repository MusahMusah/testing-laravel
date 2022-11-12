<?php

namespace App\Http\Resources\Ghost;

use Illuminate\Http\Resources\Json\ResourceCollection;

class EmptyResourceCollection extends ResourceCollection
{
    public function toArray($request)
    {
        // return empty resource collection
        return [];
    }
}
