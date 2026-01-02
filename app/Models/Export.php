<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;

class Export extends Model
{
    use AsSource;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'service',
        'userId',
        'videoId',
        'videoAspectRatio',
        'videoResolution',
        'videoOutro',
        'status',
        'file_path',
        'process_output',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'videoOutro' => 'boolean',
    ];
}
