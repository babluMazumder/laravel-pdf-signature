<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdfResponse extends Model
{
    protected $fillable = ['assignment_id', 'field_id', 'value'];

    public function assignment()
    {
        return $this->belongsTo(PdfAssignment::class, 'assignment_id');
    }

    public function field()
    {
        return $this->belongsTo(PdfField::class, 'field_id');
    }
}
