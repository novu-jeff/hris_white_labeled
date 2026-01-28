<?php

namespace App\Livewire\Admin\Settings\Users\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Edit extends Component
{

    public $id;
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

        $record = User::find($this->id);

        $this->name = $record->name;
        $this->email = $record->email;
        $this->username = $record->username;
        $this->role = $record->getRoleNames()[0];

    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($this->id),
            ],
            'username' => [
                'required',
                'string',
                Rule::unique('users', 'username')->ignore($this->id), 
            ],
            'role' => 'required|exists:roles,name', 
            'password' => [
                'nullable',
                'string',
                'min:8',
                'same:confirm_password',
            ],
            'confirm_password' => [
                'nullable',
                'string',
            ],
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
          
            $user = User::find($this->id);

            if ($user) {
                $user->name = $this->name;
                $user->username = $this->username;
                $user->email = $this->email;

                if ($this->password) {
                    $user->password = bcrypt($this->password); // Hash password if provided
                }

                $user->save();

                // Ensure only the selected role remains (avoid stacking roles like superadmin)
                $user->syncRoles([$this->role]);
            }

            $this->reset(['password', 'confirm_password']);

            DB::commit();

            $this->dispatch('alert', [
                'status' => 'success',
                'title' => 'Success!', 
                'showAlert' => true,
                'message' => 'Admin ' . strtoupper($this->name) . ' was added successfully.'
            ]);


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
        return view('livewire.admin.settings.users.admin.edit');
    }
}
