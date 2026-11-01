<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CommercialController extends Controller
{
    public function index(Request $request)
    {
        $commerciaux = User::where('role', 'commercial')
            ->when($request->q, fn ($q, $s) => $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.commerciaux.index', compact('commerciaux'));
    }

    public function create()
    {
        return view('admin.commerciaux.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'phone'             => $data['phone'] ?? null,
            'password'          => Hash::make($data['password']),
            'role'              => 'commercial',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.commerciaux.index')
            ->with('success', 'Compte commercial créé avec succès.');
    }

    public function edit(User $commercial)
    {
        abort_unless($commercial->role === 'commercial', 404);

        return view('admin.commerciaux.edit', compact('commercial'));
    }

    public function update(Request $request, User $commercial)
    {
        abort_unless($commercial->role === 'commercial', 404);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email,' . $commercial->id],
            'phone'    => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        $commercial->fill([
            'name'  => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        if (! empty($data['password'])) {
            $commercial->password = Hash::make($data['password']);
        }

        $commercial->save();

        return redirect()->route('admin.commerciaux.index')
            ->with('success', 'Compte mis à jour.');
    }

    public function toggle(User $commercial)
    {
        abort_unless($commercial->role === 'commercial', 404);

        $commercial->update(['is_active' => ! $commercial->is_active]);

        return back()->with('success', $commercial->is_active
            ? 'Compte activé.'
            : 'Compte désactivé.');
    }

    public function destroy(User $commercial)
    {
        abort_unless($commercial->role === 'commercial', 404);

        $commercial->delete(); // Soft delete : les données historiques sont conservées

        return back()->with('success', 'Compte supprimé (restaurable).');
    }
}
