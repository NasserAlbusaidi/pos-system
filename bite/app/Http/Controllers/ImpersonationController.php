<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonationController extends Controller
{
    public function impersonate(Request $request, $userId)
    {
        if (! Auth::user()?->is_super_admin) {
            abort(403, 'Unauthorized. Super Admin access required.');
        }

        $user = User::findOrFail($userId);
        $impersonatorId = Auth::id();

        Session::put('impersonator_id', $impersonatorId);

        AuditLog::create([
            'shop_id' => $user->shop_id,
            'user_id' => $impersonatorId,
            'action' => 'impersonation_start',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'meta' => [
                'impersonator_id' => $impersonatorId,
                'target_user_id' => $user->id,
                'target_email' => $user->email,
                'ip' => $request->ip(),
            ],
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function leave(Request $request)
    {
        if (! Session::has('impersonator_id')) {
            abort(403);
        }

        $originalId = Session::get('impersonator_id');

        $originalUser = User::find($originalId);
        if (! $originalUser || ! $originalUser->is_super_admin) {
            Session::forget('impersonator_id');
            Auth::logout();
            abort(403, 'Invalid impersonator session.');
        }

        $impersonatedUser = Auth::user();

        AuditLog::create([
            'shop_id' => $impersonatedUser?->shop_id,
            'user_id' => $originalId,
            'action' => 'impersonation_end',
            'auditable_type' => User::class,
            'auditable_id' => $impersonatedUser?->id,
            'meta' => [
                'impersonator_id' => $originalId,
                'target_user_id' => $impersonatedUser?->id,
                'ip' => $request->ip(),
            ],
        ]);

        Auth::login($originalUser);
        Session::forget('impersonator_id');

        return redirect()->route('super-admin.shops.index');
    }
}
