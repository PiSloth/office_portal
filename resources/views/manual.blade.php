<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manual Guide</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .markdown-body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
            line-height: 1.6;
            color: #334155;
        }
        .markdown-body h1 {
            font-size: 2.25rem;
            font-weight: 800;
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: #0f172a;
        }
        .markdown-body h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            color: #1e293b;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 0.3rem;
        }
        .markdown-body h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: 1.25rem;
            margin-bottom: 0.5rem;
            color: #334155;
        }
        .markdown-body p {
            margin-top: 0;
            margin-bottom: 1rem;
        }
        .markdown-body a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }
        .markdown-body a:hover {
            text-decoration: underline;
        }
        .markdown-body ul, .markdown-body ol {
            margin-top: 0;
            margin-bottom: 1rem;
            padding-left: 2rem;
        }
        .markdown-body li {
            margin-bottom: 0.25rem;
        }
        .markdown-body hr {
            box-sizing: content-box;
            height: 0;
            overflow: visible;
            background: transparent;
            border-bottom: 1px solid #cbd5e1;
            margin: 2rem 0;
        }
        .markdown-body strong {
            font-weight: 600;
            color: #0f172a;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 antialiased dark:bg-slate-900 dark:text-white">
    <div class="min-h-screen flex flex-col">
        <!-- Top Navigation -->
        <nav class="bg-white border-b border-gray-100 dark:bg-slate-800 dark:border-slate-700">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="shrink-0 flex items-center">
                            <a href="/" class="text-xl font-bold text-emerald-600 dark:text-emerald-400">NexGen</a>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- @auth
                            <a href="{{ route('manual.edit') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">Edit Manual</a>
                            <a href="{{ route('dashboard') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">Log in</a>
                        @endauth -->
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="flex-grow py-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                @if (session('success'))
                    <div class="mb-6 rounded-xl bg-emerald-50 p-4 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 dark:bg-slate-800 dark:border-slate-700 p-8 sm:p-12">
                    <div class="markdown-body dark:text-slate-300">
                        {!! $html !!}
                    </div>
                </div>
            </div>
        </main>
        
        <footer class="bg-white border-t border-gray-100 py-6 text-center text-sm text-gray-500 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400">
            &copy; {{ date('Y') }} NexGen. All rights reserved.
        </footer>
    </div>
</body>
</html>
