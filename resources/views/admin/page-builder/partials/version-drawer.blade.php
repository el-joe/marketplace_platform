{{-- Version history slide-in drawer --}}
<div id="version-drawer"
     x-data="{ open: false, revisions: [], loading: false }"
     x-show="open"
     x-cloak
     x-on:open-version-drawer.window="open = true; $dispatch('load-revisions')"
     class="fixed inset-0 z-50"
     style="display: none;">

    <div class="absolute inset-0 bg-black/30" x-on:click="open = false"></div>

    <aside class="absolute right-0 top-0 h-full w-96 bg-white shadow-xl flex flex-col"
           x-transition:enter="transition transform ease-out duration-200"
           x-transition:enter-start="translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition transform ease-in duration-150"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="translate-x-full">

        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-gray-900">Version history</h3>
                <p class="text-xs text-gray-500 mt-0.5">Restore any previous published version.</p>
            </div>
            <button type="button" x-on:click="open = false"
                    class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto" id="version-list">
            <template x-if="loading">
                <div class="p-6 text-center text-sm text-gray-500">Loading…</div>
            </template>

            <template x-if="!loading && revisions.length === 0">
                <div class="p-6 text-center text-sm text-gray-400">
                    No published versions yet.
                </div>
            </template>

            <template x-for="rev in revisions" :key="rev.id">
                <div class="px-4 py-3 border-b border-gray-100 hover:bg-gray-50 flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-gray-900">Version <span x-text="rev.version"></span></div>
                        <div class="text-xs text-gray-500">
                            <span x-text="rev.created_at"></span>
                            <span x-show="rev.publish_reason"> · <span x-text="rev.publish_reason"></span></span>
                        </div>
                    </div>
                    <button type="button"
                            class="px-2 py-1 text-xs font-medium text-primary-600 hover:bg-primary-50 rounded"
                            data-action="restore-page-revision"
                            :data-revision-id="rev.id">
                        Restore
                    </button>
                </div>
            </template>
        </div>
    </aside>
</div>
