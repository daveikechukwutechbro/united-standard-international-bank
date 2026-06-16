<x-foodo.layouts.app :title="'AI Advisor'" :active-page="'chat'" :full-height="true">
    {{-- Header — same design language as page-header but adapted for full-height layout --}}
    <div class="p-6 lg:p-8 pb-0 shrink-0">
        <x-foodo.page-header
            title="AI Advisor"
            subtitle="Conversational Insights — Ask anything about your restaurants"
            context-label="All Restaurants"
            :cta-route="route('foodo.dashboard')"
            cta-label="Dashboard"
            cta-icon="grid_view"
        />
    </div>

    {{-- Chat Container --}}
    <div class="flex-1 mx-6 lg:mx-8 mb-6 lg:mb-8 bg-white rounded-2xl border border-stone-200 shadow-sm flex flex-col overflow-hidden" x-data="foodoChat()">
        {{-- Metrics Bar --}}
        <x-foodo.metrics-bar :show-alert="true" alert-message="Year-over-year revenue decline detected across primary channels." />

        {{-- Chat Messages --}}
        <div class="flex-1 overflow-y-auto p-4 lg:p-6 space-y-6 bg-white foodo-scrollbar" id="chat-container">
            <template x-for="(msg, idx) in messages" :key="idx">
                <div>
                    {{-- AI Message --}}
                    <template x-if="msg.role === 'ai'">
                        <div class="flex gap-3 lg:gap-4">
                            <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center text-foodo-primary-dark shrink-0 mt-1">
                                <span class="material-symbols-outlined text-lg foodo-icon">auto_awesome</span>
                            </div>
                            <div class="flex flex-col gap-1 max-w-4xl w-full">
                                <div class="flex items-baseline gap-2">
                                    <span class="font-bold text-stone-800 text-sm">AI Advisor</span>
                                    <span class="text-xs text-stone-400" x-text="msg.time"></span>
                                </div>
                                <div class="chat-bubble-ai bg-foodo-surface-subtle border border-stone-200 p-4 lg:p-5 shadow-sm text-sm text-stone-700 leading-relaxed" x-html="msg.content"></div>
                            </div>
                        </div>
                    </template>
                    {{-- User Message --}}
                    <template x-if="msg.role === 'user'">
                        <div class="flex gap-3 lg:gap-4 flex-row-reverse">
                            <div class="w-8 h-8 rounded-full bg-foodo-primary-dark flex items-center justify-center text-white shrink-0 mt-1">
                                <span class="material-symbols-outlined text-lg foodo-icon">person</span>
                            </div>
                            <div class="flex flex-col gap-1 items-end max-w-2xl w-full">
                                <div class="flex items-baseline gap-2">
                                    <span class="font-bold text-stone-800 text-sm">Manager</span>
                                    <span class="text-xs text-stone-400" x-text="msg.time"></span>
                                </div>
                                <div class="chat-bubble-user bg-orange-50 border border-orange-200 p-4 shadow-sm text-sm text-stone-800 leading-relaxed" x-html="msg.content"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Typing indicator --}}
            <div x-show="sending" class="flex gap-3 lg:gap-4">
                <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center text-foodo-primary-dark shrink-0 mt-1">
                    <span class="material-symbols-outlined text-lg foodo-icon">auto_awesome</span>
                </div>
                <div class="chat-bubble-ai bg-foodo-surface-subtle border border-stone-200 px-5 py-4 shadow-sm">
                    <div class="flex gap-1">
                        <div class="w-2 h-2 bg-stone-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                        <div class="w-2 h-2 bg-stone-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                        <div class="w-2 h-2 bg-stone-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                    </div>
                </div>
            </div>

            <div class="h-4"></div>
        </div>

        {{-- Input --}}
        <div class="p-4 bg-white border-t border-stone-100 shrink-0">
            <div class="relative max-w-5xl mx-auto">
                <input
                    x-model="input"
                    @keydown.enter="send()"
                    class="w-full pl-4 pr-28 lg:pr-32 py-4 bg-stone-50 border border-stone-200 rounded-xl focus:ring-2 focus:ring-foodo-primary/30 focus:border-foodo-primary text-sm shadow-sm outline-none transition-all placeholder:text-stone-400 font-foodo"
                    placeholder="Ask a question about your restaurants..."
                    type="text"
                />
                <button
                    @click="send()"
                    :disabled="sending || !input.trim()"
                    class="absolute right-2 top-2 bottom-2 bg-foodo-primary-dark hover:bg-foodo-primary text-white px-4 lg:px-6 rounded-lg font-bold text-xs uppercase tracking-wider transition-colors flex items-center gap-2 shadow-md disabled:opacity-50"
                >
                    <span class="material-symbols-outlined text-lg foodo-icon">send</span>
                    <span class="hidden sm:inline">Send</span>
                </button>
            </div>
            <div class="text-center mt-3">
                <p class="text-[10px] text-stone-400">Foodo Insights — Proprietary and Confidential. AI responses should be verified.</p>
            </div>
        </div>
    </div>

    @push('scripts')
        @vite(['resources/js/foodo/chat.js'])
    @endpush
</x-foodo.layouts.app>
