<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Exception;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('roles')->paginate(10);
        $roles = Role::all();

        return view('users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'array'
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            if ($request->has('roles')) {
                $user->assignRole($request->roles);
            }

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Usuario creado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('users.index');

        } catch (Exception $e) {
            DB::rollBack();

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al crear el usuario. Inténtelo de nuevo.',
                'status' => 'error'
            ]);

            return back()->withInput();
        }
    }

    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'array'
        ]);

        DB::beginTransaction();
        try {
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
            ];

            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            $user->update($userData);

            // Sincronizar roles
            if ($request->has('roles')) {
                $user->syncRoles($request->roles);
            } else {
                $user->syncRoles([]);
            }

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Usuario actualizado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('users.index');

        } catch (Exception $e) {
            DB::rollBack();

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al actualizar el usuario. Inténtelo de nuevo.',
                'status' => 'error'
            ]);

            return back()->withInput();
        }
    }

    public function destroy(User $user)
    {
        DB::beginTransaction();
        try {
            $user->delete();

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Usuario eliminado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('users.index');

        } catch (Exception $e) {
            DB::rollBack();

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al eliminar el usuario. Inténtelo de nuevo.',
                'status' => 'error'
            ]);

            return back();
        }
    }
}
