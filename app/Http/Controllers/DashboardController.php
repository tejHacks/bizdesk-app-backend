<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\ReportController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function showLogin(): View
    {
        return view('login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function dashboard(Request $request): View
    {
        $period = in_array($request->query('period'), ['today', 'week', 'month', 'all'], true)
            ? $request->query('period')
            : 'month';

        $user = $request->user();

        return view('dashboard', [
            'user' => $user,
            'period' => $period,
            'report' => ReportController::build($user, $period),
            'recent' => $user->sales()->with('customer:id,name')->latest('sold_at')->limit(8)->get(),
            'counts' => [
                'products' => $user->products()->count(),
                'customers' => $user->customers()->count(),
            ],
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}
