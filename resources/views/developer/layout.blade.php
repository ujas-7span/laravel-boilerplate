<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Developer Portal') - {{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Instrument Sans"', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        .glow-indigo {
            box-shadow: 0 0 50px -10px rgba(99, 102, 241, 0.15);
        }
        .glow-card:hover {
            box-shadow: 0 10px 30px -10px rgba(99, 102, 241, 0.25);
        }
    </style>
</head>
<body class="h-full font-sans text-slate-200 antialiased flex flex-col justify-between">
    @if(session('developer_authenticated'))
    <nav class="border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-xl sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-violet-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                        <i data-lucide="terminal" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <span class="font-bold text-white tracking-wide text-lg">Developer Portal</span>
                        <span class="ml-2 text-xs font-mono uppercase bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 px-2 py-0.5 rounded-md">{{ app()->environment() }}</span>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <form method="POST" action="{{ route('developer.logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-slate-400 hover:text-red-400 transition-colors flex items-center space-x-1.5 py-1.5 px-3 rounded-lg hover:bg-red-500/10">
                            <i data-lucide="log-out" class="w-4 h-4"></i>
                            <span>Exit</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
    @endif

    <main class="flex-grow">
        @yield('content')
    </main>

    <footer class="border-t border-slate-800/60 bg-slate-950 py-6 text-center text-xs text-slate-500 font-mono">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row justify-between items-center space-y-2 sm:space-y-0">
            <div>{{ config('app.name', 'Laravel') }} &bull; Developer Suite</div>
            <div class="flex items-center space-x-4">
                <span>PHP {{ PHP_VERSION }}</span>
                <span>&bull;</span>
                <span>Laravel {{ app()->version() }}</span>
            </div>
        </div>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
