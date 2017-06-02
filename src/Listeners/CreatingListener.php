<?php namespace GeneaLabs\LaravelGovernor\Listeners;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatingListener
{
    public function handle(string $event, array $models)
    {
        $event = $event; // stupid workaround when not using all parameters.

        foreach ($models as $model) {
            if (auth()->check() && ! (property_exists($model, 'isGoverned') && $model['isGoverned'] === false)) {
                $model->created_by = auth()->user()->getKey();
                $table = $model->getTable();

                if (! Schema::hasColumn($table, 'created_by')) {
                    Schema::table($table, function (Blueprint $table) {
                        $table->integer('created_by')->unsigned()->nullable();
                    });
                }
            }
        }
    }
}
