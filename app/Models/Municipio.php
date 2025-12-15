<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Municipio extends Model
{
    use LogsActivity;
    protected $fillable = ['name', 'departamento_id', 'code'];

    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }

    public function comunas()
    {
        return $this->hasMany(Comuna::class);
    }

    protected function getActivityIdentifier(): string
    {
        return "Municipio: {$this->name}";
    }
}
