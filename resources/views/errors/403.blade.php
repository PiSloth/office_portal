<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#fc950f]">Access denied</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    403 Forbidden
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-white/10 bg-slate-900/95 p-8 text-white shadow-2xl">
                <div class="grid gap-10 lg:grid-cols-2 lg:items-center">
                    <div class="space-y-6">
                        <div class="inline-flex items-center gap-2 rounded-full border border-[#fc950f]/30 bg-[#fc950f]/10 px-4 py-2 text-sm font-semibold text-[#fc950f]">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 9v4m0 4h.01M10.3 3.7l-8.6 15A2 2 0 0 0 3.4 22h17.2a2 2 0 0 0 1.7-3.3l-8.6-15a2 2 0 0 0-3.4 0Z" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            You do not have permission
                        </div>

                        <div>
                            <h1 class="text-4xl font-black tracking-tight sm:text-5xl">403 Forbidden</h1>
                            <p class="mt-4 max-w-xl text-base leading-7 text-slate-300">
                                This area is restricted. If you believe you should have access, please contact your administrator.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <a href="{{ url('/') }}" class="rounded-full bg-[#7531bc] px-6 py-3 font-semibold text-white shadow-lg shadow-[#7531bc]/30 transition hover:bg-[#6729a7]">
                                Go Home
                            </a>
                            <a href="{{ url()->previous() }}" class="rounded-full border border-white/15 px-6 py-3 font-semibold text-white/90 transition hover:border-white/30 hover:bg-white/5">
                                Back
                            </a>
                        </div>
                    </div>

                    <div class="flex justify-center">
                        <div class="relative w-full max-w-xl">
                            <div class="absolute -left-6 top-10 h-24 w-24 rounded-full bg-[#7531bc]/25 blur-3xl"></div>
                            <div class="absolute -right-6 bottom-10 h-24 w-24 rounded-full bg-[#fc950f]/25 blur-3xl"></div>
                            <svg viewBox="0 0 520 420" class="relative h-auto w-full drop-shadow-2xl" role="img" aria-label="Closed door illustration">
                                <defs>
                                    <linearGradient id="doorStroke" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0%" stop-color="#7531bc" />
                                        <stop offset="100%" stop-color="#fc950f" />
                                    </linearGradient>
                                </defs>
                                <rect x="60" y="30" width="400" height="340" rx="44" fill="rgba(255,255,255,0.04)" />
                                <path d="M170 74h180v274H170z" fill="#0f172a" stroke="url(#doorStroke)" stroke-width="8" />
                                <path d="M186 92h148v238H186z" fill="#17233f" />
                                <path d="M224 92v238" stroke="rgba(255,255,255,0.08)" stroke-width="4" />
                                <circle cx="310" cy="214" r="10" fill="#fc950f" />
                                <path d="M146 350c28-38 69-58 114-58s86 20 114 58" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="10" stroke-linecap="round" />
                                <path d="M148 114l42-42m20 54V52m0 0h-54" fill="none" stroke="rgba(117,49,188,0.8)" stroke-width="12" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M388 122c18 0 32-14 32-32s-14-32-32-32-32 14-32 32 14 32 32 32Z" fill="#0f172a" stroke="#2AEFC8" stroke-width="6" />
                                <path d="M376 90h24M388 78v24" stroke="#2AEFC8" stroke-width="6" stroke-linecap="round" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
