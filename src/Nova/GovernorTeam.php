<?php namespace GeneaLabs\LaravelGovernor\Nova;

use GeneaLabs\LaravelGovernor\PermissionsTool;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Text;
use Titasgailius\SearchRelations\SearchesRelations;

class GovernorTeam extends Resource
{
    use SearchesRelations;

    public static $model;
    public static $title = "name";
    public static $search = [
        "description",
        "name",
    ];

    public function fields(Request $request) : array
    {
        return [
            Text::make("Name")
                ->sortable(),
            Text::make("Description"),
            BelongsTo::make("Owner", "ownedBy", "GeneaLabs\LaravelGovernor\Nova\GovernorUser")
                ->withMeta([
                    "belongsToId" => $this->governor_owned_by
                        ?: auth()->user()->id,
                ])
                ->searchable()
                ->prepopulate()
                ->hideFromIndex(),
            Text::make("Owner", "governor_owned_by")
                ->resolveUsing(function () {
                    if (! $this->ownedBy) {
                        return "";
                    }

                    if (! auth()->user()->can("view", $this->ownedBy)) {
                        return $this->ownedBy->name
                            ?: "";
                    }

                    return "<a href='/dashboard/resources/" . $this->ownedBy->getTable() . "/" . $this->ownedBy->getRouteKey() . "' class='no-underline dim text-primary font-bold'>" . $this->ownedBy->name . '</a>';
                })
                ->asHtml()
                ->onlyOnIndex()
                ->sortable(),

            BelongsToMany::make("Members", "members", "GeneaLabs\LaravelGovernor\Nova\GovernorUser"),
            HasMany::make("Invitations", "invitations", "GeneaLabs\LaravelGovernor\Nova\GovernorTeamInvitation"),
            PermissionsTool::make()
                ->canSee(function () {
                    return $this->governor_owned_by === auth()->user()->id
                        || auth()->user()->hasRole("SuperAdmin");
                }),
        ];
    }
}
