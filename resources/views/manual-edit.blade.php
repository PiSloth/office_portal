<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Manual Guide</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
                        <a href="{{ route('manual') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">View Manual</a>
                        <a href="{{ route('dashboard') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">Dashboard</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="flex-grow py-12">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                
                @if (session('success'))
                    <div class="mb-6 rounded-xl bg-emerald-50 p-4 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif
                
                @if ($errors->any())
                    <div class="mb-6 rounded-xl bg-rose-50 p-4 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- File Import Form -->
                    <div class="md:col-span-1">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 dark:bg-slate-800 dark:border-slate-700 p-6">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Import Markdown</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Upload a .md file to entirely replace the current manual content.</p>
                            
                            <form action="{{ route('manual.import') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-4">
                                    <input type="file" name="file" accept=".md" class="block w-full text-sm text-gray-500 dark:text-gray-400
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-full file:border-0
                                      file:text-sm file:font-semibold
                                      file:bg-emerald-50 file:text-emerald-700
                                      hover:file:bg-emerald-100
                                      dark:file:bg-emerald-900/30 dark:file:text-emerald-400 dark:hover:file:bg-emerald-800/50
                                    ">
                                </div>
                                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-full font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-500 active:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Import File
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Direct Edit Form -->
                    <div class="md:col-span-2">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 dark:bg-slate-800 dark:border-slate-700 p-6">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Edit Content Directly</h2>
                            <form action="{{ route('manual.update') }}" method="POST">
                                @csrf
                                <div class="mb-4">
                                    <label for="content" class="sr-only">Markdown Content</label>
                                    <textarea id="content" name="content" rows="20" class="block w-full rounded-xl border-gray-300 bg-gray-50 text-gray-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white font-mono text-sm p-4" placeholder="Write markdown here...">{{ old('content', $content) }}</textarea>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" class="inline-flex justify-center items-center px-6 py-2 bg-slate-900 border border-transparent rounded-full font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-800 active:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 dark:bg-slate-700 dark:hover:bg-slate-600 transition ease-in-out duration-150">
                                        Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
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
