<?php

namespace App\Livewire\Admin\Settings\Users\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Add extends Component
{

    public $name;
    public $email;
    public $role;
    public $username;
    public $password;
    public $confirm_password;
    public $roles;

    public function mount() {
        $this->loadRecords();
    }

    public function loadRecords() {
        $this->roles = Role::all();
    }

    protected function rules() {
        return [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|unique:users,username',
            'role' => 'required|exists:roles,name',
            'password' => 'required|same:confirm_password',
            'confirm_password' => 'required'
        ];
    }

    protected function messages() {
        return [
            'name.required' => 'The field name is required.',
            'email.required' => 'The field email is required.',
            'role.required' => 'The role is required',
            'role.exists' => 'The role does not exists.',
            'username.required' => 'The field username is required.',
            'password.required' => 'The field password is required.',
            'confirm_password.required' => 'The field confirm password is required.',
        ];
    }

    public function save() {

        if (Gate::denies('write users')) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Access Denied!', 
                'showAlert' => true,
                'message' => 'You do not have permission to perform this action.',
            ]);
            return;
        }

        $this->validate();

        DB::beginTransaction();

        try {
          
            $user = User::create([
                'name' => $this->name,
                'username' => $this->username,
                'email' => $this->email,
                'password' => bcrypt($this->password),
            ]);

            // Ensure the new admin has exactly one role
            $user->syncRoles([$this->role]);

            DB::commit();

            $this->dispatch('alert', [
                'status' => 'success',
                'title' => 'Success!', 
                'showAlert' => true,
                'message' => 'Admin ' . strtoupper($this->name) . ' was added successfully.'
            ]);

            $this->resetExcept('roles');

        } catch (\Exception $e) {
            
            DB::rollBack();

            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Oops!', 
                'showAlert' => true,
                'message' => 'Error occured: ' . $e->getMessage()
            ]);

        }

    }

    public function render()
    {
        return view('livewire.admin.settings.users.admin.add');
    }
}
