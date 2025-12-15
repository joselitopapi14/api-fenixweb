<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Departamento extends Model
{
    use LogsActivity;
    protected $fillable = ['name', 'code'];

    public function pais()
    {
        return $this->belongsTo(Pais::class);
    }

    public function municipios()
    {
        return $this->hasMany(Municipio::class);
    }

    protected function getActivityIdentifier(): string
    {
        return "Departamento: {$this->name}";
    }
}
