<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class UserController extends Controller
{
    public function index(): InertiaResponse
    {
        $users = User::select('id', 'name', 'email', 'created_at')->with('roles')->get();

        // dd($users);
        return Inertia::render('admin/Users/index', [
            'users' => $users,
        ]);
    }

    function create(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|exists:roles,id',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        $user->roles()->attach($validated['role']);

        return redirect()->route('usersIndex')->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user): \Illuminate\Http\RedirectResponse
    {
        // dd($request->all());
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'role' => 'required|exists:roles,id',
                'email' => 'required|email|max:255|unique:users,email,' . $user->id,
                'password' => 'nullable|string|min:8',
            ]);

            $user->name = $validated['name'];
            $user->email = $validated['email'];
            if (!empty($validated['password'])) {
                $user->password = bcrypt($validated['password']);
            }
            $user->save();
            if (isset($validated['role'])) {
                $user->roles()->sync($validated['role']);
            }

            return redirect()->route('usersIndex')->with('success', 'User updated successfully.');
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }

    public function destroy(User $user): \Illuminate\Http\RedirectResponse
    {
        $user->delete();
        return redirect()->route('usersIndex')->with('success', 'User deleted successfully.');
    }

    public function getRoles(): \Illuminate\Http\JsonResponse
    {
        $roles = Role::all();
        return Response::json($roles);
    }
}
