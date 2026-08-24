<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeInvest($request);

        $users = User::with('district')->orderBy('name')->get();

        return view('users.index', compact('users'));
    }

    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $this->authorizeInvest($request);
        $this->preventSelfManagement($request, $user);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        return back()->with('success', $user->name.' paroli yangilandi.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $this->authorizeInvest($request);
        $this->preventSelfManagement($request, $user);

        $validated = $request->validate(['is_active' => ['required', 'boolean']]);
        $user->update(['is_active' => (bool) $validated['is_active']]);

        return back()->with('success', $user->name.' holati yangilandi.');
    }

    private function authorizeInvest(Request $request): void
    {
        abort_unless($request->user()?->canManageUsers(), 403);
    }

    private function preventSelfManagement(Request $request, User $user): void
    {
        abort_if($request->user()->is($user), 422, 'O‘z hisobingizni bu sahifadan o‘zgartira olmaysiz.');
    }
}
