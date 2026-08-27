<?php

namespace App\Http\Controllers\Developer;

use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class DeveloperController extends Controller
{
    /**
     * Show the developer portal login page.
     */
    public function loginPage(): View|RedirectResponse
    {
        if (session()->get('developer_authenticated', false)) {
            return redirect()->route('developer.dashboard');
        }

        return view('developer.login');
    }

    /**
     * Authenticate developer against configured environment credentials.
     */
    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $validUsername = (string) config('developer.username', 'developer');
        $validPassword = (string) config('developer.password', 'developer123');

        $isUsernameValid = hash_equals($validUsername, (string) $validated['username']);
        $isPasswordValid = hash_equals($validPassword, (string) $validated['password']);

        if (! $isUsernameValid || ! $isPasswordValid) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['message' => 'Invalid developer credentials. Please check your .env settings.']);
        }

        $request->session()->regenerate();
        session([
            'developer_authenticated' => true,
            'developer_logged_in_at' => now()->toIso8601String(),
        ]);

        return redirect()->intended(route('developer.dashboard'));
    }

    /**
     * Render the developer dashboard with portal links and system metrics.
     */
    public function dashboard(): View
    {
        $systemInfo = [
            'app_name' => config('app.name', 'Laravel'),
            'app_env' => app()->environment(),
            'app_debug' => config('app.debug') ? 'Enabled' : 'Disabled',
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'db_connection' => config('database.default', 'sqlite'),
            'cache_driver' => config('cache.default', 'file'),
            'queue_driver' => config('queue.default', 'database'),
            'session_driver' => config('session.driver', 'file'),
            'filesystem_disk' => config('filesystems.default', 'local'),
            'telescope_enabled' => (bool) config('telescope.enabled', false),
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
        ];

        $tools = [
            [
                'name' => 'Laravel Telescope',
                'description' => 'Deep debugging and runtime inspection for requests, queries, jobs, logs, dumps, and mail.',
                'url' => url('/developer/telescope'),
                'icon' => 'telescope',
                'badge' => config('telescope.enabled') ? 'Active' : 'Disabled (Prod)',
                'badge_color' => config('telescope.enabled') ? 'green' : 'amber',
                'external' => false,
            ],
            [
                'name' => 'Laravel Horizon',
                'description' => 'Real-time dashboard and metrics for Redis queues, throughput, runtime, and failed jobs.',
                'url' => url('/developer/horizon'),
                'icon' => 'horizon',
                'badge' => 'Active',
                'badge_color' => 'indigo',
                'external' => false,
            ],
            [
                'name' => 'Log Viewer',
                'description' => 'Interactive application log viewer with search, level filters, and multi-file inspection.',
                'url' => url('/developer/log-viewer'),
                'icon' => 'log-viewer',
                'badge' => 'Active',
                'badge_color' => 'blue',
                'external' => false,
            ],
            [
                'name' => 'API Documentation',
                'description' => 'Interactive Scramble OpenAPI 3.1 documentation, endpoint schemas, and playground.',
                'url' => url('/developer/docs/api'),
                'icon' => 'docs',
                'badge' => 'OpenAPI 3.1',
                'badge_color' => 'purple',
                'external' => false,
            ],
        ];

        return view('developer.dashboard', [
            'systemInfo' => $systemInfo,
            'tools' => $tools,
        ]);
    }

    /**
     * Log out from the developer portal.
     */
    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['developer_authenticated', 'developer_logged_in_at']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('developer.login')->with('status', 'Successfully logged out from Developer Portal.');
    }
}
