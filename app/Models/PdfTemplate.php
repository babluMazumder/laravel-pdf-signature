<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdfTemplate extends Model
{
    protected $fillable = ['name', 'file_path'];

    public function fields()
    {
        return $this->hasMany(PdfField::class, 'template_id');
    }

    public function assignments()
    {
        return $this->hasMany(PdfAssignment::class, 'template_id');
    }
}
