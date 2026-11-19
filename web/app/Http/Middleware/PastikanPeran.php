<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hanya meneruskan pengguna aktif dengan peran tertentu.
 *
 * Contoh di route: ->middleware('peran:super_admin,admin')
 */
class PastikanPeran
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        $user = $request->user();

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['phone' => 'Akun ini sudah dinonaktifkan. Hubungi pengelola kontrakan.']);
        }

        abort_unless(in_array($user->role->value, $peran, true), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
