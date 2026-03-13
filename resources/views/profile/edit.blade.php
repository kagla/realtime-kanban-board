<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('설정') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Profile Information --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            {{-- Theme Settings --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl" x-data="{ theme: localStorage.getItem('darkMode') === 'true' ? 'dark' : 'light' }">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">테마 설정</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">화면 테마를 선택하세요.</p>
                        </header>

                        <div class="mt-6 flex gap-4">
                            <button @click="theme = 'light'; $store.darkMode.on = false; localStorage.setItem('darkMode', 'false')"
                                    class="flex-1 p-4 rounded-lg border-2 transition-all focus-ring"
                                    :class="theme === 'light' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'"
                                    role="radio" :aria-checked="theme === 'light'" tabindex="0">
                                <div class="flex items-center gap-3">
                                    <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">라이트</span>
                                </div>
                            </button>

                            <button @click="theme = 'dark'; $store.darkMode.on = true; localStorage.setItem('darkMode', 'true')"
                                    class="flex-1 p-4 rounded-lg border-2 transition-all focus-ring"
                                    :class="theme === 'dark' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'"
                                    role="radio" :aria-checked="theme === 'dark'" tabindex="0">
                                <div class="flex items-center gap-3">
                                    <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">다크</span>
                                </div>
                            </button>
                        </div>
                    </section>
                </div>
            </div>

            {{-- Notification Settings --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl" x-data="{
                    notifyAssignment: localStorage.getItem('notify_assignment') !== 'false',
                    notifyComment: localStorage.getItem('notify_comment') !== 'false',
                    notifyDueDate: localStorage.getItem('notify_due') !== 'false',
                    save(key, val) { localStorage.setItem(key, val); }
                }">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">알림 설정</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">받고 싶은 알림을 선택하세요.</p>
                        </header>

                        <div class="mt-6 space-y-4">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" x-model="notifyAssignment" @change="save('notify_assignment', notifyAssignment)"
                                       class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-700">
                                <div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">카드 배정 알림</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">카드가 나에게 배정되었을 때</p>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" x-model="notifyComment" @change="save('notify_comment', notifyComment)"
                                       class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-700">
                                <div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">댓글 알림</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">내 카드에 댓글이 달렸을 때</p>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" x-model="notifyDueDate" @change="save('notify_due', notifyDueDate)"
                                       class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-700">
                                <div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">마감일 알림</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">마감일이 임박한 카드가 있을 때</p>
                                </div>
                            </label>
                        </div>
                    </section>
                </div>
            </div>

            {{-- Password --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            {{-- Delete Account --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
