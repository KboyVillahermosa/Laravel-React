<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user');
        CRUD::setEntityNameStrings('user', 'users');
    }

    protected function setupListOperation()
    {
        // Columns for the list view
        CRUD::column('id');
        CRUD::column('name');
        CRUD::column('email');
        CRUD::column('created_at');
        CRUD::column('updated_at');
    }

    protected function setupCreateOperation()
    {
        // Set validation rules using the request method directly
        CRUD::setValidation([
            'name' => 'required|min:3|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        // Fields for create/edit forms
        CRUD::field('name');
        CRUD::field('email');
        CRUD::field([
            'name' => 'password',
            'type' => 'password',
        ]);
        CRUD::field([
            'name' => 'password_confirmation',
            'type' => 'password',
            'label' => 'Password Confirmation',
        ]);
    }

    protected function setupUpdateOperation()
    {
        // Set validation rules with ignoring the current user on unique email
        $user = $this->crud->getCurrentEntry();
        
        CRUD::setValidation([
            'name' => 'required|min:3|max:255',
            'email' => 'required|email|unique:users,email,' . ($user ? $user->id : ''),
            'password' => 'nullable|min:8|confirmed',
        ]);

        // Fields for update form - same as create
        CRUD::field('name');
        CRUD::field('email');
        CRUD::field([
            'name' => 'password',
            'type' => 'password',
            'hint' => 'Leave blank to keep current password',
        ]);
        CRUD::field([
            'name' => 'password_confirmation',
            'type' => 'password',
            'label' => 'Password Confirmation',
        ]);
    }

    // Override the store method to hash the password
    public function store()
    {
        $this->handlePasswordInput(request());
        return $this->traitStore();
    }

    // Override the update method to hash the password
    public function update()
    {
        $this->handlePasswordInput(request());
        return $this->traitUpdate();
    }

    // Hash password when it's set
    protected function handlePasswordInput($request)
    {
        // Remove fields that are not in the database
        $request->request->remove('password_confirmation');
        
        // Hash password if it exists in the request
        if ($request->filled('password')) {
            $request->request->set('password', Hash::make($request->input('password')));
        } else {
            $request->request->remove('password');
        }
    }
}