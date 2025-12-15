<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Pais extends Model
{
    use LogsActivity;
    protected $table = 'paises';
    protected $fillable = ['name', 'code'];

    public function departamentos()
    {
        return $this->hasMany(Departamento::class);
    }

    public function municipios()
    {
        return $this->hasManyThrough(Municipio::class, Departamento::class);
    }

    protected function getActivityIdentifier(): string
    {
        return "País: {$this->name}";
    }
}
