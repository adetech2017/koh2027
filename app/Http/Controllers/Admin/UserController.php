<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    private const ROLES = ['admin', 'editor', 'moderator'];

    public function index(): Response
    {
        Gate::authorize('manage-users');

        $users = User::query()
            ->orderByRaw('deactivated_at is not null')
            ->orderByRaw("FIELD(role, 'admin', 'editor', 'moderator')")
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'last_login_at', 'deactivated_at', 'created_at']);

        $activity = $this->lastActivity($users->pluck('id')->all());

        return Inertia::render('Admin/Users/Index', [
            'users' => $users->map(fn ($user) => [...$user->toArray(), 'last_active_at' => $activity($user)]),
            'activeAdminCount' => $this->activeAdminCount(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('manage-users');

        return Inertia::render('Admin/Users/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-users');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
            'role' => ['required', Rule::in(self::ROLES)],
        ]);

        $user = User::create($validated);

        return redirect()->route('admin.users.index')
            ->with('success', "{$user->name} can now sign in at ".route('admin.login').'. Share their password with them securely.');
    }

    public function edit(User $user): Response
    {
        Gate::authorize('manage-users');

        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                ...$user->only(['id', 'name', 'email', 'role', 'last_login_at', 'deactivated_at', 'created_at']),
                'last_active_at' => $this->lastActivity([$user->id])($user),
            ],
            'isSelf' => $user->is(auth()->user()),
            'isLastAdmin' => $this->isLastActiveAdmin($user),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in(self::ROLES)],
            // Previously the form sent a new password but it was silently ignored
            'password' => ['nullable', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);

        if ($validated['role'] !== 'admin' && $user->isAdmin()) {
            if ($user->is($request->user())) {
                return back()->withErrors(['role' => "You can't remove your own admin access. Ask another admin to do it."]);
            }
            if ($this->isLastActiveAdmin($user)) {
                return back()->withErrors(['role' => 'This is the only active admin. Make someone else an admin first.']);
            }
        }

        $changingPassword = filled($validated['password'] ?? null);
        if (!$changingPassword) {
            unset($validated['password']);
        }

        $user->update($validated);

        // A new password signs the user out on their other devices
        if ($changingPassword && !$user->is($request->user())) {
            $user->endAllSessions();
        }

        $message = $changingPassword ? "Saved. {$user->name}'s password was changed and they've been signed out on all devices." : 'User saved.';
        if ($changingPassword && $user->is($request->user())) {
            $message = 'Saved. Your password was changed.';
        }

        return redirect()->route('admin.users.index')->with('success', $message);
    }

    // Suspend or restore access without deleting the account or its history
    public function toggleAccess(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        if ($user->is($request->user())) {
            return back()->with('error', "You can't suspend your own account.");
        }

        if ($user->isDeactivated()) {
            $user->forceFill(['deactivated_at' => null])->save();

            return back()->with('success', "{$user->name} can sign in again.");
        }

        if ($this->isLastActiveAdmin($user)) {
            return back()->with('error', 'This is the only active admin and cannot be suspended.');
        }

        $user->forceFill(['deactivated_at' => now()])->save();
        $user->endAllSessions();

        return back()->with('success', "{$user->name} has been suspended and signed out.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        if ($user->is($request->user())) {
            return back()->with('error', "You can't delete your own account.");
        }
        if ($this->isLastActiveAdmin($user)) {
            return back()->with('error', 'This is the only active admin and cannot be deleted.');
        }

        $user->endAllSessions();
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', "{$user->name} was deleted. Their notes and activity history were kept.");
    }

    /**
     * When each user was last active: their most recent request (sessions are stored in the
     * database with a last_activity time) or their last sign-in, whichever is later. Sign-ins
     * alone miss anyone who was already signed in before sign-ins started being recorded.
     *
     * @return \Closure(User): ?string  ISO-8601 time, or null if never seen
     */
    private function lastActivity(array $userIds): \Closure
    {
        $sessions = config('session.driver') === 'database'
            ? \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
                ->whereIn('user_id', $userIds)
                ->selectRaw('user_id, MAX(last_activity) as last_activity')
                ->groupBy('user_id')
                ->pluck('last_activity', 'user_id')
            : collect();

        return function (User $user) use ($sessions) {
            $times = array_filter([
                isset($sessions[$user->id]) ? \Illuminate\Support\Carbon::createFromTimestamp($sessions[$user->id]) : null,
                $user->last_login_at,
            ]);

            return $times ? max($times)->toIso8601String() : null;
        };
    }

    private function activeAdminCount(): int
    {
        return User::where('role', 'admin')->whereNull('deactivated_at')->count();
    }

    private function isLastActiveAdmin(User $user): bool
    {
        return $user->isAdmin() && !$user->isDeactivated() && $this->activeAdminCount() <= 1;
    }
}
