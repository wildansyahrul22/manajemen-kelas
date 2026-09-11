<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('semesters')]
#[Fillable(['nomor', 'nama'])]
class Semester extends Model
{
    public const int JUMLAH = 14;
}
