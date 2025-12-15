<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class RedSocial extends Model
{
    use LogsActivity;
    protected $table = 'redes_sociales';

    protected $fillable = [
        'nombre',
    ];


    /**
     * Get the leaders associated with this social network.
     */


    protected function getActivityIdentifier(): string
    {
        return "Red Social: {$this->nombre}";
    }
}
