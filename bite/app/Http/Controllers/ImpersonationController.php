<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonationController extends Controller
{
    public function impersonate(Request $request, $userId)
    {
        // Ensure current user is super admin
        if (! Auth::user()?->is_super_admin) {
            abort(403, 'Unauthorized. Super Admin access required.');
        }

        $user = User::findOrFail($userId);

        // Store original ID
        Session::put('impersonator_id', Auth::id());

        Auth::login($user);

        // Audit Log (Placeholder for now, can implement table later)
        // \App\Models\Audit::create([...]);

        return redirect()->route('dashboard'); // Redirect to shop dashboard
    }

    public function leave()
    {
        if (! Session::has('impersonator_id')) {
            abort(403);
        }

        $originalId = Session::get('impersonator_id');

        Auth::loginUsingId($originalId);
        Session::forget('impersonator_id');

        return redirect()->route('super-admin.shops.index');
    }
}
