<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Threshold extends Model
{
    protected $table = 'threshold';
    protected $primaryKey = 'id_threshold';

    protected $fillable = [
        'nilai_baik',
        'nilai_warning',
        'nilai_buruk',
        'keterangan'
    ];
}