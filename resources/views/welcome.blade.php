<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NexGen - Modern Web App Creation</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .gradient-text {
            background: linear-gradient(135deg, #7531bc 0%, #fc950f 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .bg-primary {
            background-color: #7531bc;
        }

        .bg-secondary {
            background-color: #fc950f;
        }

        .hover-bg-primary:hover {
            background-color: #5d2596;
        }

        .text-primary {
            color: #7531bc;
        }

        .border-primary {
            border-color: #7531bc;
        }

        .blob-1 {
            background: radial-gradient(circle, rgba(117, 49, 188, 0.4) 0%, rgba(117, 49, 188, 0) 70%);
        }

        .blob-2 {
            background: radial-gradient(circle, rgba(252, 149, 15, 0.3) 0%, rgba(252, 149, 15, 0) 70%);
        }

        .blob-3 {
            background: radial-gradient(circle, rgba(42, 239, 200, 0.2) 0%, rgba(42, 239, 200, 0) 70%);
        }
    </style>
</head>

<body
    class="antialiased bg-slate-950 text-white relative overflow-x-hidden min-h-screen flex flex-col justify-center items-center">

    <!-- Background Elements -->
    <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
        <div class="absolute top-[-10%] left-[-10%] w-[50vw] h-[50vw] blob-1 rounded-full mix-blend-screen opacity-70 animate-pulse"
            style="animation-duration: 8s;"></div>
        <div class="absolute bottom-[-20%] right-[-10%] w-[60vw] h-[60vw] blob-2 rounded-full mix-blend-screen opacity-70 animate-pulse"
            style="animation-duration: 10s;"></div>
        <div class="absolute top-[20%] right-[20%] w-[40vw] h-[40vw] blob-3 rounded-full mix-blend-screen opacity-50 animate-pulse"
            style="animation-duration: 12s;"></div>
    </div>

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 py-6 px-8 flex justify-end">
        @if (Route::has('login'))
            <div class="space-x-4">
                @auth
                    <a href="{{ url('/dashboard') }}"
                        class="px-6 py-2.5 rounded-full backdrop-blur-md bg-white/10 border border-white/20 text-sm font-semibold hover:bg-white/20 transition duration-300">Dashboard</a>
                @else
                    <a href="{{ route('login') }}"
                        class="px-6 py-2.5 rounded-full backdrop-blur-md bg-white/10 border border-white/20 text-sm font-semibold hover:bg-white/20 transition duration-300">Log
                        In</a>
                @endauth
            </div>
        @endif
    </nav>

    <!-- Main Content -->
    <main class="relative z-10 container mx-auto px-6 py-12 flex flex-col items-center justify-center text-center">

        <!-- Logo Container -->
        {{-- <div
            class="mb-10 p-6 rounded-3xl backdrop-blur-xl bg-white/5 border border-white/10 shadow-2xl transition transform hover:scale-105 hover:shadow-[#7531bc]/20 duration-500 cursor-default">
            <x-application-logo class="w-40 h-40 drop-shadow-2xl" />
        </div> --}}

        <div class="max-w-3xl space-y-6">
            <div
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 border border-[#fc950f]/30 text-[#fc950f] text-sm font-semibold tracking-wide backdrop-blur-sm mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                Next Generation Solutions
            </div>

            <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight">
                Modern <span class="gradient-text">Web App</span> Creation
            </h1>

            <h2 class="text-2xl md:text-3xl font-medium text-slate-300 mt-4">
                Solving Business Barriers & Bottlenecks
            </h2>

            <p class="text-lg md:text-xl text-slate-400 mt-6 max-w-2xl mx-auto leading-relaxed">
                We enhance your business effectively with our creativity. So, it is <strong
                    class="text-white font-semibold">more than code</strong>, this is <strong
                    class="text-[#fc950f] font-semibold">business logic</strong>.
            </p>
        </div>

        <div class="mt-12 flex flex-col sm:flex-row gap-4 items-center">
            @auth
                <a href="{{ url('/dashboard') }}"
                    class="px-8 py-4 rounded-full bg-primary hover-bg-primary text-white font-bold text-lg shadow-lg shadow-[#7531bc]/30 transition duration-300 transform hover:-translate-y-1">
                    Enter Application
                </a>
            @else
                <a href="{{ route('login') }}"
                    class="px-8 py-4 rounded-full bg-primary hover-bg-primary text-white font-bold text-lg shadow-lg shadow-[#7531bc]/30 transition duration-300 transform hover:-translate-y-1 flex items-center gap-2">
                    Access Portal
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3">
                        </path>
                    </svg>
                </a>
            @endauth
        </div>

        <!-- Features Glassmorphic Row -->
        <div class="mt-24 grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl">
            <div
                class="p-6 rounded-2xl backdrop-blur-md bg-white/5 border border-white/10 hover:bg-white/10 transition duration-300 group">
                <div
                    class="w-12 h-12 rounded-full bg-[#7531bc]/20 flex items-center justify-center text-[#7531bc] mb-4 group-hover:scale-110 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Creative Solutions</h3>
                <p class="text-slate-400 text-sm">We don't just build software. We engineer creative pathways that
                    eliminate daily operational friction.</p>
            </div>

            <div
                class="p-6 rounded-2xl backdrop-blur-md bg-white/5 border border-white/10 hover:bg-white/10 transition duration-300 group">
                <div
                    class="w-12 h-12 rounded-full bg-[#fc950f]/20 flex items-center justify-center text-[#fc950f] mb-4 group-hover:scale-110 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Enhance Effectively</h3>
                <p class="text-slate-400 text-sm">Scale faster with applications designed explicitly to optimize your
                    unique business workflows.</p>
            </div>

            <div
                class="p-6 rounded-2xl backdrop-blur-md bg-white/5 border border-white/10 hover:bg-white/10 transition duration-300 group">
                <div
                    class="w-12 h-12 rounded-full bg-[#2AEFC8]/20 flex items-center justify-center text-[#2AEFC8] mb-4 group-hover:scale-110 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Business Logic Core</h3>
                <p class="text-slate-400 text-sm">Every line of code serves a purpose: automating tasks, enforcing
                    rules, and securing your enterprise data.</p>
            </div>
        </div>

    </main>
</body>

</html>
