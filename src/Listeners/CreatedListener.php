<?php namespace GeneaLabs\LaravelGovernor\Listeners;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class CreatedListener
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function handle(string $event, array $models)
    {
        if (str_contains($event, "Hyn\Tenancy\Models\Website")
            || str_contains($event, "Hyn\Tenancy\Models\Hostname")
            || ! Schema::hasTable('roles')
        ) {
            return;
        }

        collect($models)
            ->filter(function ($model) {
                return $model instanceof Model
                    && get_class($model) === config('genealabs-laravel-governor.models.auth');
            })
            ->each(function ($model) {
                $model->roles()->attach('Member');
            });
    }
}
