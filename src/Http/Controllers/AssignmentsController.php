<?php namespace GeneaLabs\LaravelGovernor\Http\Controllers;

use GeneaLabs\LaravelGovernor\Assignment;
use GeneaLabs\LaravelGovernor\Http\Requests\CreateAssignmentRequest;
use GeneaLabs\LaravelGovernor\Role;

class AssignmentsController extends Controller
{
    protected $user;
    protected $displayNameField;

    public function __construct()
    {
        parent::__construct();

        $this->displayNameField = config('genealabs-laravel-governor.user-name-property');
        $this->user = app(config('genealabs-laravel-governor.auth-model'));
    }

    /**
     * @return mixed
     */
    public function edit()
    {
        $assignment = new Assignment();
        $this->authorize('view', $assignment);
        $displayNameField = $this->displayNameField;
        $users = $this->user->all();
        $roles = Role::with('users')->get();


        return view('genealabs-laravel-governor::assignments.edit')->with(
            compact('users', 'roles', 'displayNameField', 'assignment')
        );
    }

    /**
     * @return mixed
     */
    public function update(CreateAssignmentRequest $request)
    {
        $assignment = new Assignment();
        $assignment->removeAllUsersFromRoles();
        $assignedUsers = $request->get('users');
        $assignment->assignUsersToRoles($assignedUsers);
        $assignment->addAllUsersToMemberRole();
        $assignment->removeAllSuperAdminUsersFromOtherRoles($assignedUsers);

        return redirect()
            ->route('genealabs.laravel-governor.assignments.edit', 0);
    }
}
