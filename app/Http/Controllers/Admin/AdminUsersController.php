<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUsersController extends Controller
{
    public function index()
    {
        return view('admin.users.index');
    }

    public function table(Request $request)
    {
        $limit  = (int) $request->input('limit', 5);
        $offset = (int) $request->input('offset', 0);
        $search = trim((string) $request->input('search', ''));

        $q = User::query()->select(['id', 'name', 'email', 'is_admin', 'created_at']);

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $allRow = (clone $q)->count();

        $list = $q->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'is_admin' => (bool) $u->is_admin,
                'created_at' => optional($u->created_at)->format('Y-m-d H:i'),
            ]);

        return response()->json([
            'list' => $list,
            'allRow' => $allRow,
            'row' => $list->count(),
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_admin' => ['required', 'in:0,1'],
        ]);

        User::create([
            'name' => mb_strtoupper(trim($data['name'])),
            'email' => strtolower(trim($data['email'])),
            'password' => Hash::make($data['password']),
            'is_admin' => (int) $data['is_admin'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Usuario creado correctamente.',
        ]);
    }

    public function delete(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);

        if ((int) auth()->id() === (int) $data['id']) {
            return response()->json([
                'status' => false,
                'message' => 'No puedes eliminar tu propio usuario.',
            ]);
        }

        User::where('id', $data['id'])->delete();

        return response()->json([
            'status' => true,
            'message' => 'Usuario eliminado.',
        ]);
    }

    // (OPCIONAL) si activas rutas edit/update:
    /*
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'email' => ['required','email','max:190', Rule::unique('users','email')->ignore($user->id)],
            'password' => ['nullable','string','min:8','confirmed'],
            'is_admin' => ['required','in:0,1'],
        ]);

        $user->name = mb_strtoupper(trim($data['name']));
        $user->email = strtolower(trim($data['email']));
        $user->is_admin = (int)$data['is_admin'];

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()->route('usuarios')->with('success', 'Usuario actualizado correctamente.');
    }
    */
}
