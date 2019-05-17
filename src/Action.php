<?php namespace GeneaLabs\LaravelGovernor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Action extends Model
{
    protected $primaryKey = 'name';
    protected $rules = [
        'name' => 'required|min:3|unique:governor_actions,name',
    ];
    protected $fillable = [
        'name',
    ];
    protected $table = "governor_actions";

    public $incrementing = false;

    public function permissions() : HasMany
    {
        return $this->hasMany(
            config('genealabs-laravel-governor.models.permission'),
            'action_name'
        );
    }
}
