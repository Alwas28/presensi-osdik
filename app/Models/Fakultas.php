<?php

namespace App\Models;

use Database\Factories\FakultasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name'])]
class Fakultas extends Model
{
    /** @use HasFactory<FakultasFactory> */
    use HasFactory;

    /**
     * @return HasMany<ProgramStudi, $this>
     */
    public function programStudis(): HasMany
    {
        return $this->hasMany(ProgramStudi::class);
    }
}
