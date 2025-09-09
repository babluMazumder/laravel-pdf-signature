<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdfAssignment extends Model
{
    protected $fillable = ['template_id', 'user_id', 'status', 'final_path'];

    public function template()
    {
        return $this->belongsTo(PdfTemplate::class, 'template_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function responses()
    {
        return $this->hasMany(PdfResponse::class, 'assignment_id');
    }
}
