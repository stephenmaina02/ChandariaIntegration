<div>
   <div class="m-3">
        <button class="rounded-sm px-4 py-2 text-white bg-blue-500" wire:click="openModal">Create New User</button>

        <table class="table-auto w-full mt-3">
            <thead>
                <tr class="bg-teal-500 text-white">
                    <th class="border px-2 py-4">Name</th>
                    <th class="border px-2 py-4">Email</th>
                    <th class="border px-2 py-4">Role</th>
                    <th class="border px-2 py-4" colspan="2">Options</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                <tr>
                    <td class="border px-2 py-4">{{ $user->name }}</td>
                    <td class="border px-2 py-4">{{ $user->email }}</td>
                    <td class="border px-2 py-4"><select class="border-2 w-full" wire:model="role">
                        @if($user->role=='user')
                            <option value="{{ $user->role }}">{{ $user->role }}</option>
                            <option value="admin">Admin</option>
                        @else
                            <option value="{{ $user->role }}">{{ $user->role }}</option>
                            <option value="user">User</option>
                        @endif
                    </select></td>
                    <td class="border px-2 py-4"><button class="px-2 py-2 rounded bg-purple-500" wire:click="updateRole({{ $user->id }})">Update Role</button></td>
                    <td class="border px-2 py-4"><button class="px-2 py-2 rounded bg-red-500" wire:click="deleteUser({{ $user->id }})">Delete User</button></td>
                </tr>
                @endforeach

            </tbody>
        </table>

        <x-jet-dialog-modal wire:model="userModal" id="userModal">
            <x-slot name="title">
                {{ __('Add New User') }}
            </x-slot>
            <x-slot name="content">
                <form wire:submit.prevent="saveUser">
                <div class="mt-3">
                    <x-jet-label for="name" value="{{ __('Name') }}" />
                    <x-jet-input id="name" type="text" class="mt-1 block w-full" wire:model="name" />
                    <x-jet-input-error for="name" class="mt-1" />
                </div>
                <div class="mt-3">
                    <x-jet-label for="email" value="{{ __('Email') }}" />
                    <x-jet-input id="email" type="email" wire:model="email" class="mt-1 block w-full"/>
                    <x-jet-input-error for="email" class="mt-1" />
                </div>
                <div class="mt-3">
                    <x-jet-label for="password" value="{{ __('Password') }}" />
                    <x-jet-input id="password" type="password" class="mt-1 block w-full" wire:model="password" />
                    <x-jet-input-error for="password" class="mt-1" />
                </div>
                <div class="mt-3">
                    <x-jet-label for="role" value="{{ __('Role') }}" />
                    <select name="role" id="role" wire:model="role" class="mt-1 block w-full py-2 rounded border-2">
                        <option value="">Select role...</option>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                    <x-jet-input-error for="role" class="mt-1" />
                </div>

                <button type="submit" class="w-full rounded shadow-md mt-3 bg-gradient-to-r from-red-800 to-pink-600 text-white mt-3 p-2">Submit</button>
            </form>
            </x-slot>

            <x-slot name="footer">

            </x-slot>
        </x-jet-dialog-modal>
    </div>
</div>
