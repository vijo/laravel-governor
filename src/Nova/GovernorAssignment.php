<?php namespace GeneaLabs\LaravelGovernor\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\BelongsTo;

class GovernorAssignment extends Resource
{
    public static $model;
    public static $title = "name";
    public static $globallySearchable = false;

    public function fields(Request $request)
    {
        return [
            Text::make("name")
                ->sortable(),
            BelongsTo::make("User", "user", GovernorUser::class),
        ];
    }
}
