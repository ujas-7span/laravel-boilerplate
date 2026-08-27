@extends('developer.layout')

@section('title', 'Developer Suite')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">
    <!-- Developer Tools Grid -->
    <div>
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-white flex items-center space-x-2.5">
                <i data-lucide="cpu" class="w-5 h-5 text-indigo-400"></i>
                <span>Developer Suites & Tools</span>
            </h2>
            <span class="text-xs text-slate-400 font-mono">4 Integrated Engines</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- 1. Laravel Telescope -->
            <a href="{{ url('/developer/telescope') }}" target="_blank" class="group bg-slate-900/60 hover:bg-slate-900/90 border border-slate-800/90 hover:border-indigo-500/50 rounded-2xl p-6 transition-all duration-300 flex flex-col justify-between glow-card">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i data-lucide="search" class="w-6 h-6"></i>
                        </div>
                        @if($systemInfo['telescope_enabled'])
                            <span class="text-xs font-mono font-medium px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Active</span>
                        @else
                            <span class="text-xs font-mono font-medium px-2.5 py-1 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20">Disabled (Prod)</span>
                        @endif
                    </div>
                    <h3 class="text-lg font-bold text-white group-hover:text-indigo-400 transition-colors">Laravel Telescope</h3>
                    <p class="mt-2 text-xs text-slate-400 leading-relaxed">Deep runtime inspection for HTTP requests, database queries, exceptions, queued jobs, mail, and cache hits.</p>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-800/60 flex items-center justify-between text-xs font-semibold text-indigo-400 group-hover:text-indigo-300">
                    <span>Open Telescope</span>
                    <i data-lucide="arrow-up-right" class="w-4 h-4 transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"></i>
                </div>
            </a>

            <!-- 2. Laravel Horizon -->
            <a href="{{ url('/developer/horizon') }}" target="_blank" class="group bg-slate-900/60 hover:bg-slate-900/90 border border-slate-800/90 hover:border-violet-500/50 rounded-2xl p-6 transition-all duration-300 flex flex-col justify-between glow-card">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-violet-500/10 border border-violet-500/20 text-violet-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i data-lucide="layers" class="w-6 h-6"></i>
                        </div>
                        <span class="text-xs font-mono font-medium px-2.5 py-1 rounded-full bg-violet-500/10 text-violet-400 border border-violet-500/20">Queues</span>
                    </div>
                    <h3 class="text-lg font-bold text-white group-hover:text-violet-400 transition-colors">Laravel Horizon</h3>
                    <p class="mt-2 text-xs text-slate-400 leading-relaxed">Real-time metrics for Redis background queues, job throughput, wait times, runtime monitoring, and failed jobs.</p>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-800/60 flex items-center justify-between text-xs font-semibold text-violet-400 group-hover:text-violet-300">
                    <span>Open Horizon</span>
                    <i data-lucide="arrow-up-right" class="w-4 h-4 transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"></i>
                </div>
            </a>

            <!-- 3. Log Viewer -->
            <a href="{{ url('/developer/log-viewer') }}" target="_blank" class="group bg-slate-900/60 hover:bg-slate-900/90 border border-slate-800/90 hover:border-sky-500/50 rounded-2xl p-6 transition-all duration-300 flex flex-col justify-between glow-card">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-sky-500/10 border border-sky-500/20 text-sky-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i data-lucide="file-text" class="w-6 h-6"></i>
                        </div>
                        <span class="text-xs font-mono font-medium px-2.5 py-1 rounded-full bg-sky-500/10 text-sky-400 border border-sky-500/20">Logs</span>
                    </div>
                    <h3 class="text-lg font-bold text-white group-hover:text-sky-400 transition-colors">Log Viewer</h3>
                    <p class="mt-2 text-xs text-slate-400 leading-relaxed">Interactive log inspection with real-time streaming, severity filters (emergency, error, debug), and full-text search.</p>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-800/60 flex items-center justify-between text-xs font-semibold text-sky-400 group-hover:text-sky-300">
                    <span>Open Log Viewer</span>
                    <i data-lucide="arrow-up-right" class="w-4 h-4 transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"></i>
                </div>
            </a>

            <!-- 4. Scramble API Documentation -->
            <a href="{{ url('/developer/docs/api') }}" target="_blank" class="group bg-slate-900/60 hover:bg-slate-900/90 border border-slate-800/90 hover:border-purple-500/50 rounded-2xl p-6 transition-all duration-300 flex flex-col justify-between glow-card">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i data-lucide="book-marked" class="w-6 h-6"></i>
                        </div>
                        <span class="text-xs font-mono font-medium px-2.5 py-1 rounded-full bg-purple-500/10 text-purple-400 border border-purple-500/20">OpenAPI 3.1</span>
                    </div>
                    <h3 class="text-lg font-bold text-white group-hover:text-purple-400 transition-colors">API Documentation</h3>
                    <p class="mt-2 text-xs text-slate-400 leading-relaxed">Interactive Scramble OpenAPI 3.1 playground, dynamic query parameters, request schemas, and TypeScript exports.</p>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-800/60 flex items-center justify-between text-xs font-semibold text-purple-400 group-hover:text-purple-300">
                    <span>Open API Docs</span>
                    <i data-lucide="arrow-up-right" class="w-4 h-4 transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- System Architecture & Environment Metrics -->
    <div>
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-white flex items-center space-x-2.5">
                <i data-lucide="server" class="w-5 h-5 text-indigo-400"></i>
                <span>Environment & Architecture Metrics</span>
            </h2>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-4 font-mono text-xs">
            <div class="bg-slate-900/60 border border-slate-800/80 rounded-xl p-4">
                <div class="text-slate-500 mb-1 text-[11px] uppercase">Environment</div>
                <div class="font-bold text-white text-sm">{{ $systemInfo['app_env'] }}</div>
            </div>

            <div class="bg-slate-900/60 border border-slate-800/80 rounded-xl p-4">
                <div class="text-slate-500 mb-1 text-[11px] uppercase">Debug Mode</div>
                <div class="font-bold {{ $systemInfo['app_debug'] === 'Enabled' ? 'text-amber-400' : 'text-emerald-400' }} text-sm">{{ $systemInfo['app_debug'] }}</div>
            </div>

            <div class="bg-slate-900/60 border border-slate-800/80 rounded-xl p-4">
                <div class="text-slate-500 mb-1 text-[11px] uppercase">Database</div>
                <div class="font-bold text-white text-sm">{{ $systemInfo['db_connection'] }}</div>
            </div>

            <div class="bg-slate-900/60 border border-slate-800/80 rounded-xl p-4">
                <div class="text-slate-500 mb-1 text-[11px] uppercase">Cache Driver</div>
                <div class="font-bold text-white text-sm">{{ $systemInfo['cache_driver'] }}</div>
            </div>

            <div class="bg-slate-900/60 border border-slate-800/80 rounded-xl p-4">
                <div class="text-slate-500 mb-1 text-[11px] uppercase">Queue Driver</div>
                <div class="font-bold text-white text-sm">{{ $systemInfo['queue_driver'] }}</div>
            </div>

            <div class="bg-slate-900/60 border border-slate-800/80 rounded-xl p-4">
                <div class="text-slate-500 mb-1 text-[11px] uppercase">Session Driver</div>
                <div class="font-bold text-white text-sm">{{ $systemInfo['session_driver'] }}</div>
            </div>

            <div class="bg-slate-900/60 border border-slate-800/80 rounded-xl p-4">
                <div class="text-slate-500 mb-1 text-[11px] uppercase">Storage Disk</div>
                <div class="font-bold text-white text-sm">{{ $systemInfo['filesystem_disk'] }}</div>
            </div>

            <div class="bg-slate-900/60 border border-slate-800/80 rounded-xl p-4">
                <div class="text-slate-500 mb-1 text-[11px] uppercase">API Version</div>
                <div class="font-bold text-white text-sm">v1 (REST)</div>
            </div>
        </div>
    </div>
</div>
@endsection
