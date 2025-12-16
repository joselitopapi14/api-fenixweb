<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductoCollection extends ResourceCollection
{
    protected $stats;

    public function __construct($resource, $stats = [])
    {
        parent::__construct($resource);
        $this->stats = $stats;
    }

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'stats' => $this->stats,
        ];
    }
}
