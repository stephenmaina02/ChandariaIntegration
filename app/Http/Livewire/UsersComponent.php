<?php

namespace App\Http\Livewire;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;

class UsersComponent extends Component
{
    public $users;
    public $userModal=false;
    public $name, $email, $password, $role;


    public function clear()
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role = '';
    }
    protected $rules = [
        'name' => 'required|min:3',
        'email' => 'required|email',
        'password' => 'required|min:6',
        'role' => 'required'
    ];

    public function updated($propertyName)
     {
         $this->validateOnly($propertyName);
     }

    public function mount()
    {
       $this->users=User::all();
    }
    public function openModal()
    {
        $this->userModal=true;
    }

    public function saveUser()
    {
        $this->validate();
        $user=new User();
        $user->name=$this->name;
        $user->email=$this->email;
        $user->password=Hash::make($this->password);
        $user->role=$this->role;
        $user->save();
        $this->userModal=false;
        $this->mount();
        $this->clear();

    }
    public function updateRole($id)
    {
        $user=User::find($id);
        $user->role=$this->role;
        $user->save();
        $this->mount();
    }
    public function deleteUser($id)
    {
        $user=User::find($id);
        $user->delete();
        $this->mount();
    }

    public function render()
    {
        return view('livewire.users-component');
    }
}
