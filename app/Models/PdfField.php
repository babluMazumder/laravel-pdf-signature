<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdfField extends Model
{
    protected $fillable = [
        'template_id', 'type', 'page', 'x', 'y', 'width', 'height', 'required', 'label'
    ];

    public function template()
    {
        return $this->belongsTo(PdfTemplate::class, 'template_id');
    }

    public function responses()
    {
        return $this->hasMany(PdfResponse::class, 'field_id');
    }
}
