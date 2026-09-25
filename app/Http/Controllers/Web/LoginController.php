<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\JwtService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private JwtService $jwt) {}

    public function show(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route(auth()->user()->preferredHomeRouteName());
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $identifier = (string) $request->input('email', '');
        $password = (string) $request->input('password', '');
        $remember = $request->boolean('remember_me');
        $safeInput = $request->only(['email', 'remember_me']);

        if (trim($identifier) === '' || $password === '') {
            return back()->withErrors(['email' => 'Company email and password are required'])->withInput($safeInput);
        }

        $user = $this->jwt->findUserByIdentifier($identifier);
        if (! $user || ! $this->jwt->verifyPassword($user, $password)) {
            return back()->withErrors(['email' => 'Invalid email or password'])->withInput($safeInput);
        }

        if (! $user->is_active) {
            return back()->withErrors(['email' => 'User account is deactivated'])->withInput($safeInput);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $user = $this->jwt->loadUserWithRbac((int) $user->id);

        if (! $user || ! $user->hasInvoiceLaunchRole()) {
            return back()->withErrors([
                'email' => 'Invoice system only — your role is not enabled for this launch',
            ])->withInput($safeInput);
        }

        $request->session()->regenerate();
        auth()->login($user, $remember);

        return redirect()->intended(route($user->preferredHomeRouteName()));
    }

    public function logout(Request $request): RedirectResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login', ['loggedOut' => 'true']);
    }

    public function idleLogout(Request $request): RedirectResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login', ['sessionExpired' => 'true']);
    }
}
