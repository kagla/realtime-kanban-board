<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-4 min-w-0">
                <a href="{{ route('boards.index') }}" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition focus-ring rounded" aria-label="보드 목록으로 돌아가기">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div class="min-w-0">
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight truncate">{{ $board->title }}</h2>
                    @if ($board->description)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 truncate">{{ $board->description }}</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
                {{-- Connection Status --}}
                <span x-data="{ connected: false }" x-init="$watch('$store.wsConnected', v => connected = v)"
                      class="hidden sm:flex items-center gap-1.5 text-xs text-gray-400"
                      :title="connected ? 'WebSocket 연결됨' : 'WebSocket 연결 끊김'">
                    <span class="connection-dot" :class="connected ? 'connected' : 'disconnected'" role="status" :aria-label="connected ? '연결됨' : '연결 끊김'"></span>
                    <span x-text="connected ? '연결됨' : '재연결 중...'" class="hidden md:inline"></span>
                </span>

                {{-- Notification Bell --}}
                <div x-data="notificationBell()" class="relative">
                    <button @click="toggle()" class="relative p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition focus-ring rounded" aria-label="알림" aria-haspopup="true" :aria-expanded="open">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span x-show="unreadCount > 0" x-cloak class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center" x-text="unreadCount > 9 ? '9+' : unreadCount" aria-label="읽지 않은 알림"></span>
                    </button>
                    <div x-show="open" x-cloak @click.away="open = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50 max-h-96 overflow-y-auto" role="menu" aria-label="알림 목록">
                        <div class="p-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">알림</span>
                            <button x-show="unreadCount > 0" @click="markAllRead()" class="text-xs text-indigo-600 hover:underline focus-ring rounded px-1">모두 읽음</button>
                        </div>
                        <template x-if="notifications.length === 0">
                            <p class="p-4 text-sm text-gray-400 text-center">알림이 없습니다.</p>
                        </template>
                        <template x-for="n in notifications" :key="n.id">
                            <div @click="markRead(n)" class="p-3 border-b border-gray-50 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition" :class="n.read_at ? 'opacity-60' : ''" role="menuitem" tabindex="0" @keydown.enter="markRead(n)">
                                <p class="text-sm text-gray-700 dark:text-gray-300" x-text="n.data.message"></p>
                                <p class="text-xs text-gray-400 mt-1" x-text="timeAgo(n.created_at)"></p>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Export Menu --}}
                <div x-data="{ exportOpen: false }" class="relative">
                    <button @click="exportOpen = !exportOpen" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition focus-ring rounded" aria-label="내보내기" aria-haspopup="true" :aria-expanded="exportOpen">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </button>
                    <div x-show="exportOpen" x-cloak @click.away="exportOpen = false" x-transition class="absolute right-0 mt-2 w-44 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50" role="menu">
                        <a href="/api/boards/{{ $board->id }}/export/json" class="block px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-t-lg" role="menuitem">JSON으로 내보내기</a>
                        <a href="/api/boards/{{ $board->id }}/export/markdown" class="block px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-b-lg" role="menuitem">Markdown으로 내보내기</a>
                    </div>
                </div>

                {{-- Dark Mode Toggle --}}
                <button @click="$store.darkMode.toggle()" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition focus-ring rounded" aria-label="다크 모드 전환">
                    <svg x-show="!$store.darkMode.on" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg x-show="$store.darkMode.on" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>

                {{-- Sidebar Toggle (mobile) --}}
                <button @click="$dispatch('toggle-sidebar')" class="sm:hidden p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition focus-ring rounded" aria-label="사이드바 열기">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                @if ($currentRole !== 'viewer')
                <a href="{{ route('boards.edit', $board) }}" class="hidden sm:inline-flex items-center px-3 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition focus-ring">수정</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="h-[calc(100vh-130px)] overflow-hidden flex"
         x-data="kanbanBoard()"
         x-init="init()"
         @toggle-sidebar.window="showSidebar = !showSidebar">

        {{-- Main Board Area --}}
        <div class="flex-1 h-full overflow-x-auto p-3 sm:p-6 flex flex-col">
            {{-- Search & Filter Bar --}}
            <div class="mb-4 flex flex-wrap items-center gap-2 sm:gap-3" role="toolbar" aria-label="검색 및 필터">
                <div class="relative flex-shrink-0 w-full sm:w-64">
                    <input type="text" x-model="searchQuery" @input.debounce.300ms="doSearch()"
                           placeholder="카드 검색..." aria-label="카드 검색"
                           class="w-full pl-9 pr-8 py-2 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <button x-show="searchQuery" x-cloak @click="searchQuery = ''; searchResults = []" class="absolute right-2 top-2.5 text-gray-400 hover:text-gray-600" aria-label="검색 지우기">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <select x-model="filterPriority" @change="applyFilters()" aria-label="우선순위 필터" class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">우선순위</option>
                        <option value="urgent">Urgent</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                    <select x-model="filterAssignee" @change="applyFilters()" aria-label="담당자 필터" class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">담당자</option>
                        <template x-for="u in allUsers" :key="u.id"><option :value="u.id" x-text="u.name"></option></template>
                    </select>
                    <select x-model="filterDue" @change="applyFilters()" aria-label="마감일 필터" class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">마감일</option>
                        <option value="today">오늘</option>
                        <option value="this_week">이번 주</option>
                        <option value="overdue">지난 마감일</option>
                    </select>
                    <button x-show="filterPriority || filterAssignee || filterDue || searchQuery" @click="clearFilters()" class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 underline focus-ring rounded px-1">필터 초기화</button>
                    <div x-show="filteredCardIds !== null" x-cloak class="text-xs text-gray-500 dark:text-gray-400"><span x-text="filteredCardIds ? filteredCardIds.length + '개 카드 매치' : ''"></span></div>
                </div>
            </div>

            {{-- Search Results Dropdown --}}
            <div x-show="searchResults.length > 0" x-cloak x-transition class="mb-4 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 max-h-60 overflow-y-auto" role="listbox" aria-label="검색 결과">
                <div class="p-2 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <span class="text-xs text-gray-500">검색 결과 (<span x-text="searchResults.length"></span>)</span>
                    <button @click="searchResults = []; searchQuery = ''" class="text-xs text-gray-400 hover:text-gray-600 focus-ring rounded px-1">닫기</button>
                </div>
                <template x-for="sr in searchResults" :key="sr.id">
                    <div @click="scrollToCard(sr)" @keydown.enter="scrollToCard(sr)" tabindex="0" role="option" class="p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-50 dark:border-gray-700 focus-ring">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100" x-html="highlightMatch(sr.title, searchQuery)"></span>
                            <span class="text-xs text-gray-400" x-text="sr.column_title"></span>
                        </div>
                        <p x-show="sr.description" class="text-xs text-gray-500 mt-1 line-clamp-1" x-html="highlightMatch(sr.description || '', searchQuery)"></p>
                    </div>
                </template>
            </div>

            {{-- Columns --}}
            <div class="flex gap-4 sm:gap-5 flex-1 items-start overflow-x-auto pb-2 columns-mobile-scroll" id="columns-container" role="region" aria-label="칸반 보드">
                <template x-for="column in columns" :key="column.id">
                    <div class="flex-shrink-0 w-[85vw] sm:w-72 bg-gray-100 dark:bg-gray-800 rounded-lg flex flex-col max-h-full transition-all duration-300"
                         :data-column-id="column.id" :class="column._highlight ? 'ring-2 ring-indigo-400 card-highlight' : ''" role="region" :aria-label="column.title + ' 컬럼'">
                        <div class="p-3 flex items-center justify-between cursor-grab column-drag-handle">
                            <div class="flex items-center gap-2 min-w-0">
                                <template x-if="editingColumnId !== column.id">
                                    <h3 class="font-semibold text-sm text-gray-700 dark:text-gray-300 uppercase tracking-wide cursor-text truncate" @dblclick="canEdit && startEditColumn(column)" x-text="column.title" role="heading" aria-level="3"></h3>
                                </template>
                                <template x-if="editingColumnId === column.id">
                                    <input type="text" x-model="editingColumnTitle" @keydown.enter="saveColumnTitle(column)" @keydown.escape="editingColumnId = null" @blur="saveColumnTitle(column)" aria-label="컬럼 제목 수정" class="text-sm font-semibold border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded px-2 py-1 w-40 focus:ring-indigo-500 focus:border-indigo-500">
                                </template>
                                <span class="bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 text-xs font-medium px-2 py-0.5 rounded-full" x-text="visibleCards(column).length" aria-label="카드 수"></span>
                            </div>
                            <button x-show="canEdit" @click="deleteColumn(column)" class="text-gray-400 hover:text-red-500 transition p-1 focus-ring rounded" :aria-label="'컬럼 삭제: ' + column.title">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="flex-1 overflow-y-auto px-3 pb-3 space-y-2 min-h-[60px] cards-container" :data-column-id="column.id">
                            <div x-show="visibleCards(column).length === 0 && !searchQuery && filteredCardIds === null" class="py-8 text-center">
                                <svg class="mx-auto w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">카드가 없습니다</p>
                            </div>
                            <template x-for="card in visibleCards(column)" :key="card.id">
                                <div class="bg-white dark:bg-gray-700 rounded-lg shadow-sm p-3 hover:shadow-md transition-all duration-200 cursor-pointer border border-gray-200 dark:border-gray-600 focus-ring"
                                     :data-card-id="card.id" :class="card._highlight ? 'ring-2 ring-blue-400 scale-[1.02] card-highlight' : ''"
                                     @click="openCardDetail(card, column)" @keydown.enter="openCardDetail(card, column)" tabindex="0" role="article" :aria-label="card.title">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="card.title"></p>
                                    <p x-show="card.description" class="mt-1 text-xs text-gray-500 dark:text-gray-400 line-clamp-2" x-text="card.description"></p>
                                    <div class="mt-2 flex items-center justify-between">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" :class="priorityClass(card.priority)" x-text="card.priority"></span>
                                        <div class="flex items-center gap-2">
                                            <span x-show="card.due_date" class="text-xs" :class="isOverdue(card.due_date) ? 'text-red-500 font-medium' : 'text-gray-400 dark:text-gray-500'" x-text="formatDate(card.due_date)"></span>
                                            <span x-show="card.assigned_user" class="inline-flex items-center justify-center w-6 h-6 bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 rounded-full text-xs font-medium" :title="card.assigned_user?.name" x-text="card.assigned_user?.name?.charAt(0) ?? ''"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div x-show="canEdit" class="p-3 pt-0">
                            <button @click="openAddCard(column)" class="w-full text-left text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-md px-3 py-2 transition focus-ring" :aria-label="'카드 추가: ' + column.title + ' 컬럼'">+ 카드 추가</button>
                        </div>
                    </div>
                </template>

                {{-- Empty board state --}}
                <div x-show="columns.length === 0 && canEdit" class="flex-shrink-0 w-full max-w-md mx-auto py-16 text-center">
                    <svg class="mx-auto w-16 h-16 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/></svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-700 dark:text-gray-300">아직 컬럼이 없습니다</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">첫 번째 컬럼을 추가하여 칸반 보드를 시작하세요.</p>
                    <button @click="showColumnModal = true" class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 transition focus-ring">+ 첫 컬럼 추가</button>
                </div>

                <div x-show="canEdit && columns.length > 0" class="flex-shrink-0 w-[85vw] sm:w-72 column-add-btn">
                    <button @click="showColumnModal = true" class="w-full bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg p-4 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition text-left border-2 border-dashed border-gray-300 dark:border-gray-600 focus-ring" aria-label="새 컬럼 추가">+ 컬럼 추가</button>
                </div>
            </div>
        </div>

        {{-- Right Sidebar --}}
        <div class="sidebar-transition fixed sm:relative inset-y-0 right-0 z-40 sm:z-auto w-80 flex-shrink-0 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col h-full shadow-xl sm:shadow-none"
             x-show="showSidebar" x-cloak
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full sm:translate-x-0 opacity-0" x-transition:enter-end="translate-x-0 opacity-100"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0 opacity-100" x-transition:leave-end="translate-x-full sm:translate-x-0 opacity-0"
             role="complementary" aria-label="사이드바">
            <div class="sm:hidden fixed inset-0 bg-black/30 -z-10" @click="showSidebar = false"></div>
            <div class="flex border-b border-gray-200 dark:border-gray-700" role="tablist">
                <button @click="sidebarTab = 'activity'" role="tab" :aria-selected="sidebarTab === 'activity'" class="flex-1 px-3 py-2.5 text-xs font-medium transition focus-ring" :class="sidebarTab === 'activity' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'">활동 로그</button>
                <button @click="sidebarTab = 'members'" role="tab" :aria-selected="sidebarTab === 'members'" class="flex-1 px-3 py-2.5 text-xs font-medium transition focus-ring" :class="sidebarTab === 'members' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'">멤버 (<span x-text="boardMembers.length"></span>)</button>
                <button @click="showSidebar = false" class="px-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus-ring rounded" aria-label="사이드바 닫기"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>

            {{-- Activity Tab --}}
            <div x-show="sidebarTab === 'activity'" class="flex-1 flex flex-col overflow-hidden" role="tabpanel">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="mb-3" x-show="onlineUsers.length > 0">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">접속 중 (<span x-text="onlineUsers.length"></span>)</p>
                        <div class="flex flex-wrap gap-1">
                            <template x-for="u in onlineUsers" :key="u.id"><span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 border-2 border-white dark:border-gray-800 shadow-sm" :title="u.name" x-text="u.name.charAt(0)"></span></template>
                        </div>
                    </div>
                    <div class="flex gap-1" role="group" aria-label="활동 필터">
                        <template x-for="f in [{key:'all',label:'전체'},{key:'card',label:'카드'},{key:'column',label:'컬럼'}]" :key="f.key">
                            <button @click="filterActivities(f.key)" class="px-2.5 py-1 text-xs rounded-full transition focus-ring" :class="activityFilter === f.key ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 font-medium' : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700'" x-text="f.label" :aria-pressed="activityFilter === f.key"></button>
                        </template>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto p-4 space-y-3">
                    <template x-if="activities.length === 0"><p class="text-sm text-gray-400 text-center py-8">활동 기록이 없습니다.</p></template>
                    <template x-for="act in activities" :key="act.id">
                        <div class="flex gap-3 text-sm" :class="act._new ? 'card-enter' : ''">
                            <span class="flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-xs font-medium mt-0.5" :class="act.target_type === 'card' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : 'bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300'" x-text="act.user_name.charAt(0)"></span>
                            <div class="flex-1 min-w-0">
                                <p class="text-gray-700 dark:text-gray-300 leading-snug" x-html="formatActivity(act)"></p>
                                <p class="text-xs text-gray-400 mt-0.5" x-text="timeAgo(act.created_at)"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Members Tab --}}
            <div x-show="sidebarTab === 'members'" class="flex-1 flex flex-col overflow-hidden" role="tabpanel">
                <div x-show="currentRole === 'owner'" class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="relative">
                        <input type="text" x-model="memberSearchQuery" @input.debounce.300ms="searchMemberUsers()" placeholder="이메일 또는 이름으로 검색..." aria-label="멤버 검색" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <div x-show="memberSearchResults.length > 0" x-cloak x-transition class="absolute left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg z-10 max-h-40 overflow-y-auto" role="listbox">
                            <template x-for="u in memberSearchResults" :key="u.id">
                                <div @click="inviteMember(u)" @keydown.enter="inviteMember(u)" tabindex="0" role="option" class="p-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer flex items-center justify-between focus-ring">
                                    <div><p class="text-sm text-gray-800 dark:text-gray-200" x-text="u.name"></p><p class="text-xs text-gray-400" x-text="u.email"></p></div>
                                    <span class="text-xs text-indigo-600">초대</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto p-4 space-y-3">
                    <template x-for="m in boardMembers" :key="m.user_id">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-medium" :class="m.role === 'owner' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300' : m.role === 'editor' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : 'bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300'" x-text="m.name.charAt(0)"></span>
                                <div><p class="text-sm font-medium text-gray-800 dark:text-gray-200" x-text="m.name"></p><p class="text-xs text-gray-400" x-text="roleLabel(m.role)"></p></div>
                            </div>
                            <div x-show="currentRole === 'owner' && m.role !== 'owner'" class="flex items-center gap-1">
                                <select @change="updateMemberRole(m, $event.target.value)" :value="m.role" aria-label="역할 변경" class="text-xs border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded py-0.5 px-1"><option value="editor">편집자</option><option value="viewer">뷰어</option></select>
                                <button @click="removeMember(m)" class="text-gray-400 hover:text-red-500 p-0.5 focus-ring rounded" :aria-label="'멤버 제거: ' + m.name"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Sidebar Toggle (desktop) --}}
        <button x-show="!showSidebar" x-cloak @click="showSidebar = true; loadActivities()" class="hidden sm:block fixed right-4 top-1/2 -translate-y-1/2 z-40 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-l-lg shadow-md px-2 py-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition focus-ring" aria-label="사이드바 열기">
            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </button>

        {{-- Column Add Modal --}}
        <div x-show="showColumnModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showColumnModal = false" role="dialog" aria-modal="true" aria-label="새 컬럼 추가">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="modal-backdrop" @click="showColumnModal = false"></div>
                <div class="modal-content bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md z-10 p-6" @click.stop>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">새 컬럼 추가</h3>
                    <form @submit.prevent="addColumn()">
                        <input type="text" x-model="newColumnTitle" placeholder="컬럼 제목" x-ref="newColumnInput" aria-label="컬럼 제목" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 mb-4" required>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showColumnModal = false" class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 focus-ring">취소</button>
                            <button type="submit" :disabled="loading" class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50 focus-ring inline-flex items-center gap-2"><span x-show="loading" class="spinner spinner-sm"></span>추가</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Card Add/Edit Modal --}}
        <div x-show="showCardModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showCardModal = false" role="dialog" aria-modal="true" :aria-label="cardForm.id ? '카드 수정' : '새 카드 추가'">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="modal-backdrop" @click="showCardModal = false"></div>
                <div class="modal-content bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg z-10 p-6" @click.stop>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4" x-text="cardForm.id ? '카드 수정' : '새 카드 추가'"></h3>
                    <form @submit.prevent="saveCard()">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="card-title">제목 <span class="text-red-500">*</span></label>
                                <input id="card-title" type="text" x-model="cardForm.title" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                <p x-show="formErrors.title" x-cloak class="mt-1 text-xs text-red-500" x-text="formErrors.title"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="card-desc">설명</label>
                                <textarea id="card-desc" x-model="cardForm.description" rows="3" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="card-priority">우선순위</label>
                                    <select id="card-priority" x-model="cardForm.priority" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="card-due">마감일</label>
                                    <input id="card-due" type="date" x-model="cardForm.due_date" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="card-assignee">담당자</label>
                                <select id="card-assignee" x-model="cardForm.assigned_user_id" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"><option value="">미지정</option><template x-for="u in allUsers" :key="u.id"><option :value="u.id" x-text="u.name"></option></template></select>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" @click="showCardModal = false" class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 focus-ring">취소</button>
                            <button type="submit" :disabled="loading" class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50 focus-ring inline-flex items-center gap-2"><span x-show="loading" class="spinner spinner-sm"></span><span x-text="cardForm.id ? '수정' : '추가'"></span></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Card Detail Modal --}}
        <div x-show="showDetailModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showDetailModal = false" role="dialog" aria-modal="true" aria-label="카드 상세">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="modal-backdrop" @click="showDetailModal = false"></div>
                <div class="modal-content bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl z-10 max-h-[85vh] flex flex-col" @click.stop>
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-start justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="detailCard?.title"></h3>
                            <button @click="showDetailModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus-ring rounded" aria-label="닫기"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                        </div>
                        <div class="mt-4 space-y-3">
                            <div x-show="detailCard?.description">
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">설명</label>
                                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap" x-text="detailCard?.description"></p>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">우선순위</label><span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium" :class="priorityClass(detailCard?.priority)" x-text="detailCard?.priority"></span></div>
                                <div><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">마감일</label><span class="text-sm" :class="isOverdue(detailCard?.due_date) ? 'text-red-500 font-medium' : 'text-gray-700 dark:text-gray-300'" x-text="detailCard?.due_date ? formatDate(detailCard.due_date) : '미설정'"></span></div>
                                <div><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">담당자</label><span class="text-sm text-gray-700 dark:text-gray-300" x-text="detailCard?.assigned_user?.name ?? '미지정'"></span></div>
                            </div>
                        </div>
                        <div class="mt-4 flex gap-3" x-show="canEdit">
                            <button @click="deleteCard(detailCard)" class="px-3 py-1.5 text-sm text-red-600 bg-white dark:bg-gray-700 border border-red-300 dark:border-red-700 rounded-md hover:bg-red-50 dark:hover:bg-red-900/20 focus-ring">삭제</button>
                            <button @click="openEditCard(detailCard)" class="px-3 py-1.5 text-sm text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus-ring">수정</button>
                        </div>
                    </div>
                    <div class="flex-1 overflow-y-auto p-6" aria-label="댓글">
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">댓글 (<span x-text="cardComments.length"></span>)</h4>
                        <div class="space-y-3 mb-4">
                            <template x-if="cardComments.length === 0"><p class="text-sm text-gray-400 py-4 text-center">아직 댓글이 없습니다.</p></template>
                            <template x-for="c in cardComments" :key="c.id">
                                <div class="flex gap-3">
                                    <span class="flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-xs font-medium bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 mt-0.5" x-text="c.user_name.charAt(0)"></span>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2"><span class="text-sm font-medium text-gray-800 dark:text-gray-200" x-text="c.user_name"></span><span class="text-xs text-gray-400" x-text="timeAgo(c.created_at)"></span><button x-show="c.user_id === currentUserId || currentRole === 'owner'" @click="deleteComment(c)" class="text-xs text-gray-400 hover:text-red-500 ml-auto focus-ring rounded px-1" aria-label="댓글 삭제">삭제</button></div>
                                        <p class="text-sm text-gray-700 dark:text-gray-300 mt-0.5 whitespace-pre-wrap" x-text="c.content"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div x-show="currentRole !== 'viewer'" class="flex gap-2">
                            <textarea x-model="newComment" rows="2" placeholder="댓글을 입력하세요..." aria-label="댓글 입력" @keydown.meta.enter="addComment()" @keydown.ctrl.enter="addComment()" class="flex-1 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            <button @click="addComment()" :disabled="!newComment.trim() || loading" class="self-end px-3 py-2 text-sm text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50 focus-ring inline-flex items-center gap-1" aria-label="댓글 전송"><span x-show="loading" class="spinner spinner-sm"></span>전송</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Toast Container --}}
        <div class="toast-container" aria-live="polite" aria-atomic="true">
            <template x-for="t in toasts" :key="t.id">
                <div class="toast-item" :class="[t.type === 'success' ? 'bg-green-600' : t.type === 'error' ? 'bg-red-600' : 'bg-blue-600', t.removing ? 'removing' : '']" role="alert">
                    <svg x-show="t.type === 'success'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <svg x-show="t.type === 'error'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-text="t.message"></span>
                </div>
            </template>
        </div>

        {{-- Loading Overlay --}}
        <div x-show="globalLoading" x-cloak class="fixed inset-0 z-[90] bg-black/20 backdrop-blur-[1px] flex items-center justify-center">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl px-6 py-4 flex items-center gap-3"><span class="spinner text-indigo-600"></span><span class="text-sm text-gray-700 dark:text-gray-300" x-text="loadingMessage">처리 중...</span></div>
        </div>

        {{-- Reconnection Banner --}}
        <div x-show="showReconnectBanner" x-cloak x-transition class="fixed top-0 inset-x-0 z-[80] bg-yellow-500 text-yellow-900 text-center text-sm py-2 px-4 flex items-center justify-center gap-2" role="alert">
            <span class="spinner spinner-sm text-yellow-900"></span>
            WebSocket 연결이 끊겼습니다. 재연결 중...
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
    <script>
        const boardId = {{ $board->id }};
        const currentUserId = {{ auth()->id() }};
        const currentRole = @json($currentRole);
        const apiBase = `/api/boards/${boardId}`;

        function notificationBell() {
            return {
                open: false, notifications: [], unreadCount: 0,
                async toggle() { this.open = !this.open; if (this.open) await this.load(); },
                async load() { try { const res = await axios.get('/api/notifications'); this.notifications = res.data.data; this.unreadCount = res.data.unread_count; } catch (e) { console.error(e); } },
                async markRead(n) { if (n.read_at) return; try { await axios.post(`/api/notifications/${n.id}/read`); n.read_at = new Date().toISOString(); this.unreadCount = Math.max(0, this.unreadCount - 1); } catch (e) { console.error(e); } },
                async markAllRead() { try { await axios.post('/api/notifications/read-all'); this.notifications.forEach(n => n.read_at = new Date().toISOString()); this.unreadCount = 0; } catch (e) { console.error(e); } },
                timeAgo(dateStr) { const now = new Date(), date = new Date(dateStr), diff = Math.floor((now - date) / 1000); if (diff < 60) return '방금 전'; if (diff < 3600) return `${Math.floor(diff / 60)}분 전`; if (diff < 86400) return `${Math.floor(diff / 3600)}시간 전`; return `${Math.floor(diff / 86400)}일 전`; },
            };
        }

        function kanbanBoard() {
            return {
                columns: @json($boardData), allUsers: @json($users), boardMembers: @json($membersData),
                currentRole, canEdit: currentRole === 'owner' || currentRole === 'editor',
                showColumnModal: false, newColumnTitle: '', editingColumnId: null, editingColumnTitle: '',
                showCardModal: false, cardForm: { id: null, title: '', description: '', priority: 'medium', due_date: '', assigned_user_id: '', column_id: null },
                formErrors: {}, currentColumn: null,
                showDetailModal: false, detailCard: null, detailColumn: null, cardComments: [], newComment: '',
                showSidebar: window.innerWidth >= 640, sidebarTab: 'activity', activities: [], activityFilter: 'all', onlineUsers: [],
                searchQuery: '', searchResults: [], filterPriority: '', filterAssignee: '', filterDue: '', filteredCardIds: null,
                memberSearchQuery: '', memberSearchResults: [],
                loading: false, globalLoading: false, loadingMessage: '처리 중...',
                toasts: [], toastId: 0, showReconnectBanner: false, reconnectAttempts: 0,

                init() {
                    this.$nextTick(() => this.initSortable());
                    this.$watch('showColumnModal', (val) => { if (val) this.$nextTick(() => this.$refs.newColumnInput?.focus()); });
                    this.loadActivities();
                    this.listenForEvents();
                    window.addEventListener('online', () => this.showToast('네트워크가 복구되었습니다.', 'success'));
                    window.addEventListener('offline', () => this.showToast('네트워크 연결이 끊겼습니다.', 'error'));
                },

                async doSearch() { if (!this.searchQuery.trim()) { this.searchResults = []; return; } try { const res = await axios.get(`${apiBase}/search`, { params: { q: this.searchQuery } }); this.searchResults = res.data.data; } catch (e) { this.handleApiError(e, '검색 중 오류가 발생했습니다.'); } },
                async applyFilters() { if (!this.filterPriority && !this.filterAssignee && !this.filterDue) { this.filteredCardIds = null; return; } try { const params = {}; if (this.filterPriority) params.priority = this.filterPriority; if (this.filterAssignee) params.assigned_user_id = this.filterAssignee; if (this.filterDue) params.due_filter = this.filterDue; const res = await axios.get(`${apiBase}/filter`, { params }); this.filteredCardIds = res.data.data.map(c => c.id); } catch (e) { this.handleApiError(e, '필터 적용 중 오류가 발생했습니다.'); } },
                clearFilters() { this.filterPriority = ''; this.filterAssignee = ''; this.filterDue = ''; this.searchQuery = ''; this.searchResults = []; this.filteredCardIds = null; },
                visibleCards(column) { if (this.filteredCardIds === null) return column.cards; return column.cards.filter(c => this.filteredCardIds.includes(c.id)); },
                highlightMatch(text, query) { if (!query || !text) return this.escHtml(text); const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); const regex = new RegExp(`(${escaped})`, 'gi'); return this.escHtml(text).replace(regex, '<mark class="bg-yellow-200 dark:bg-yellow-800 px-0.5 rounded">$1</mark>'); },
                scrollToCard(sr) { this.searchResults = []; this.searchQuery = ''; const col = this.columns.find(c => c.id === sr.column_id); if (!col) return; const card = col.cards.find(c => c.id === sr.id); if (card) { card._highlight = true; setTimeout(() => card._highlight = false, 3000); } this.$nextTick(() => { const el = document.querySelector(`[data-card-id="${sr.id}"]`); if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }); },
                isOverdue(dateStr) { if (!dateStr) return false; return new Date(dateStr + 'T23:59:59') < new Date(); },

                async loadComments(cardId) { try { const res = await axios.get(`${apiBase}/cards/${cardId}/comments`); this.cardComments = res.data.data; } catch (e) { this.handleApiError(e, '댓글을 불러올 수 없습니다.'); } },
                async addComment() { if (!this.newComment.trim() || !this.detailCard) return; this.loading = true; try { const res = await axios.post(`${apiBase}/cards/${this.detailCard.id}/comments`, { content: this.newComment.trim() }); this.cardComments.unshift(res.data.data); this.newComment = ''; } catch (e) { this.handleApiError(e, '댓글 작성에 실패했습니다.'); } this.loading = false; },
                async deleteComment(comment) { if (!confirm('댓글을 삭제하시겠습니까?')) return; try { await axios.delete(`${apiBase}/cards/${this.detailCard.id}/comments/${comment.id}`); this.cardComments = this.cardComments.filter(c => c.id !== comment.id); } catch (e) { this.handleApiError(e, '댓글 삭제에 실패했습니다.'); } },

                async searchMemberUsers() { if (this.memberSearchQuery.length < 2) { this.memberSearchResults = []; return; } try { const res = await axios.get(`${apiBase}/members/search-users`, { params: { q: this.memberSearchQuery } }); this.memberSearchResults = res.data.data; } catch (e) { console.error(e); } },
                async inviteMember(user) { try { const res = await axios.post(`${apiBase}/members`, { user_id: user.id, role: 'editor' }); this.boardMembers.push(res.data.data); this.allUsers.push({ id: user.id, name: user.name }); this.memberSearchQuery = ''; this.memberSearchResults = []; this.showToast('멤버가 추가되었습니다.'); } catch (e) { this.handleApiError(e, '멤버 추가 실패'); } },
                async updateMemberRole(member, newRole) { try { const m = this.boardMembers.find(bm => bm.user_id === member.user_id); await axios.put(`${apiBase}/members/${member.id || member.user_id}`, { role: newRole }); if (m) m.role = newRole; this.showToast('역할이 변경되었습니다.'); } catch (e) { this.handleApiError(e, '역할 변경 실패'); } },
                async removeMember(member) { if (!confirm(`${member.name}님을 보드에서 제거하시겠습니까?`)) return; try { await axios.delete(`${apiBase}/members/${member.id || member.user_id}`); this.boardMembers = this.boardMembers.filter(m => m.user_id !== member.user_id); this.showToast('멤버가 제거되었습니다.'); } catch (e) { this.handleApiError(e, '멤버 제거 실패'); } },
                roleLabel(role) { return { owner: '소유자', editor: '편집자', viewer: '뷰어' }[role] || role; },

                async loadActivities() { try { const filterParam = this.activityFilter !== 'all' ? `?filter=${this.activityFilter}` : ''; const res = await axios.get(`${apiBase}/activities${filterParam}`); this.activities = res.data.data; } catch (e) { console.error('Failed to load activities', e); } },
                async filterActivities(filter) { this.activityFilter = filter; await this.loadActivities(); },
                formatActivity(act) {
                    const name = `<strong>${this.escHtml(act.user_name)}</strong>`;
                    const meta = act.metadata || {};
                    const target = act.target_type === 'card' ? '카드' : '컬럼';
                    const title = meta.card_title || meta.column_title || '';
                    const titleHtml = title ? `'<strong>${this.escHtml(title)}</strong>'` : '';
                    switch (act.action) {
                        case 'created': return `${name}님이 ${titleHtml} ${target}를 생성했습니다.`;
                        case 'updated': return `${name}님이 ${titleHtml} ${target}를 수정했습니다.`;
                        case 'deleted': return `${name}님이 ${titleHtml} ${target}를 삭제했습니다.`;
                        case 'moved': const to = meta.to_column ? `'<strong>${this.escHtml(meta.to_column)}</strong>'` : ''; return `${name}님이 ${titleHtml} 카드를 ${to}(으)로 이동했습니다.`;
                        case 'reordered': return `${name}님이 ${titleHtml} 컬럼 순서를 변경했습니다.`;
                        default: return `${name}님이 ${target}에 작업했습니다.`;
                    }
                },
                escHtml(str) { if (!str) return ''; const div = document.createElement('div'); div.textContent = str; return div.innerHTML; },
                timeAgo(dateStr) { const now = new Date(), date = new Date(dateStr), diff = Math.floor((now - date) / 1000); if (diff < 60) return '방금 전'; if (diff < 3600) return `${Math.floor(diff / 60)}분 전`; if (diff < 86400) return `${Math.floor(diff / 3600)}시간 전`; return `${Math.floor(diff / 86400)}일 전`; },

                listenForEvents() {
                    if (typeof window.Echo === 'undefined') { console.warn('Laravel Echo not loaded'); return; }
                    const presenceChannel = window.Echo.join(`board.${boardId}`)
                        .here((users) => { this.onlineUsers = users; Alpine.store('wsConnected', true); this.showReconnectBanner = false; this.reconnectAttempts = 0; })
                        .joining((user) => { if (!this.onlineUsers.find(u => u.id === user.id)) this.onlineUsers.push(user); })
                        .leaving((user) => { this.onlineUsers = this.onlineUsers.filter(u => u.id !== user.id); })
                        .error((err) => { Alpine.store('wsConnected', false); console.error('[Echo] Presence error:', err); this.handleWsDisconnect(); });
                    const privateChannel = window.Echo.private(`board.${boardId}`);
                    privateChannel.listen('.CardCreated', (e) => this.onCardCreated(e));
                    privateChannel.listen('.CardUpdated', (e) => this.onCardUpdated(e));
                    privateChannel.listen('.CardDeleted', (e) => this.onCardDeleted(e));
                    privateChannel.listen('.CardMoved', (e) => this.onCardMoved(e));
                    privateChannel.listen('.ColumnCreated', (e) => this.onColumnCreated(e));
                    privateChannel.listen('.ColumnUpdated', (e) => this.onColumnUpdated(e));
                    privateChannel.listen('.ColumnDeleted', (e) => this.onColumnDeleted(e));
                    privateChannel.listen('.ActivityLogged', (e) => this.onActivityLogged(e));
                    privateChannel.listen('.CommentCreated', (e) => this.onCommentCreated(e));
                    if (window.Echo.connector?.pusher) {
                        const p = window.Echo.connector.pusher;
                        p.connection.bind('connected', () => { Alpine.store('wsConnected', true); this.showReconnectBanner = false; this.reconnectAttempts = 0; });
                        p.connection.bind('disconnected', () => { Alpine.store('wsConnected', false); this.handleWsDisconnect(); });
                        p.connection.bind('unavailable', () => { Alpine.store('wsConnected', false); this.handleWsDisconnect(); });
                    }
                },
                handleWsDisconnect() { this.showReconnectBanner = true; this.reconnectAttempts++; setTimeout(() => { if (!Alpine.store('wsConnected') && window.Echo.connector?.pusher) window.Echo.connector.pusher.connect(); }, 30000); },

                onCardCreated(e) { const col = this.columns.find(c => c.id === e.column_id); if (!col) return; if (col.cards.find(c => c.id === e.card.id)) return; e.card._highlight = true; col.cards.push(e.card); this.showToast('다른 사용자가 카드를 추가했습니다.', 'info'); setTimeout(() => { e.card._highlight = false; }, 2000); },
                onCardUpdated(e) { for (const col of this.columns) { const idx = col.cards.findIndex(c => c.id === e.card.id); if (idx !== -1) { const merged = { ...col.cards[idx], ...e.card, _highlight: true }; col.cards.splice(idx, 1, merged); setTimeout(() => { merged._highlight = false; }, 2000); break; } } },
                onCardDeleted(e) { const col = this.columns.find(c => c.id === e.column_id); if (col) col.cards = col.cards.filter(c => c.id !== e.card_id); if (this.detailCard?.id === e.card_id) this.showDetailModal = false; },
                onCardMoved(e) { const fromCol = this.columns.find(c => c.id === e.from_column_id); const toCol = this.columns.find(c => c.id === e.to_column_id); if (!fromCol || !toCol) return; const cardIdx = fromCol.cards.findIndex(c => c.id === e.card_id); if (cardIdx === -1) return; const card = fromCol.cards.splice(cardIdx, 1)[0]; card.column_id = e.to_column_id; card._highlight = true; toCol.cards.splice(e.position, 0, card); fromCol.cards.forEach((c, i) => c.position = i); toCol.cards.forEach((c, i) => c.position = i); setTimeout(() => { card._highlight = false; }, 2000); },
                onColumnCreated(e) { if (this.columns.find(c => c.id === e.column.id)) return; const col = { ...e.column, cards: [], _highlight: true }; this.columns.push(col); this.showToast('다른 사용자가 컬럼을 추가했습니다.', 'info'); this.initCardSortables(); setTimeout(() => { col._highlight = false; }, 2000); },
                onColumnUpdated(e) { const col = this.columns.find(c => c.id === e.column.id); if (col) { col.title = e.column.title; col.position = e.column.position; col._highlight = true; setTimeout(() => { col._highlight = false; }, 2000); } this.columns.sort((a, b) => a.position - b.position); },
                onColumnDeleted(e) { this.columns = this.columns.filter(c => c.id !== e.column_id); },
                onActivityLogged(e) { e.activity._new = true; this.activities.unshift(e.activity); if (this.activities.length > 20) this.activities.pop(); setTimeout(() => { e.activity._new = false; }, 3000); },
                onCommentCreated(e) { if (this.detailCard && this.detailCard.id === e.comment.card_id) { if (!this.cardComments.find(c => c.id === e.comment.id)) this.cardComments.unshift(e.comment); } },

                initSortable() {
                    if (!this.canEdit) return;
                    const columnsEl = document.getElementById('columns-container');
                    if (columnsEl) {
                        Sortable.create(columnsEl, { animation: 250, handle: '.column-drag-handle', draggable: '[data-column-id]', ghostClass: 'sortable-ghost', dragClass: 'sortable-drag', forceFallback: true, fallbackTolerance: 5,
                            onEnd: (evt) => { const columnId = parseInt(evt.item.dataset.columnId); const newIndex = evt.newIndex; const col = this.columns.find(c => c.id === columnId); if (!col) return; this.columns.splice(evt.oldIndex, 1); this.columns.splice(newIndex, 0, col); this.columns.forEach((c, i) => c.position = i); this.apiPost(`${apiBase}/columns/${columnId}/reorder`, { position: newIndex }); }
                        });
                    }
                    this.initCardSortables();
                },
                initCardSortables() {
                    if (!this.canEdit) return;
                    this.$nextTick(() => {
                        document.querySelectorAll('.cards-container').forEach(el => {
                            if (el._sortable) el._sortable.destroy();
                            el._sortable = Sortable.create(el, { group: 'cards', animation: 250, ghostClass: 'sortable-ghost', dragClass: 'sortable-drag', draggable: '[data-card-id]', forceFallback: true, fallbackTolerance: 5, delay: 150, delayOnTouchOnly: true, touchStartThreshold: 3,
                                onEnd: (evt) => { const cardId = parseInt(evt.item.dataset.cardId); const toColumnId = parseInt(evt.to.dataset.columnId); const newPosition = evt.newIndex; const fromColumnId = parseInt(evt.from.dataset.columnId); const fromCol = this.columns.find(c => c.id === fromColumnId); const toCol = this.columns.find(c => c.id === toColumnId); if (!fromCol || !toCol) return; const cardIndex = fromCol.cards.findIndex(c => c.id === cardId); if (cardIndex === -1) return; const card = fromCol.cards.splice(cardIndex, 1)[0]; card.column_id = toColumnId; toCol.cards.splice(newPosition, 0, card); fromCol.cards.forEach((c, i) => c.position = i); toCol.cards.forEach((c, i) => c.position = i); this.apiPost(`${apiBase}/cards/${cardId}/move`, { column_id: toColumnId, position: newPosition }); }
                            });
                        });
                    });
                },

                async addColumn() { if (!this.newColumnTitle.trim()) return; this.loading = true; try { const res = await this.apiPost(`${apiBase}/columns`, { title: this.newColumnTitle.trim() }); this.columns.push({ ...res.data, cards: [] }); this.newColumnTitle = ''; this.showColumnModal = false; this.showToast('컬럼이 추가되었습니다.'); this.initCardSortables(); this.loadActivities(); } catch (e) { this.handleApiError(e, '컬럼 추가에 실패했습니다.'); } this.loading = false; },
                startEditColumn(column) { this.editingColumnId = column.id; this.editingColumnTitle = column.title; },
                async saveColumnTitle(column) { if (!this.editingColumnTitle.trim() || this.editingColumnTitle === column.title) { this.editingColumnId = null; return; } try { await this.apiPut(`${apiBase}/columns/${column.id}`, { title: this.editingColumnTitle.trim() }); column.title = this.editingColumnTitle.trim(); this.loadActivities(); } catch (e) { this.handleApiError(e, '컬럼 수정에 실패했습니다.'); } this.editingColumnId = null; },
                async deleteColumn(column) { if (!confirm(`"${column.title}" 컬럼과 포함된 모든 카드를 삭제하시겠습니까?`)) return; try { await this.apiDelete(`${apiBase}/columns/${column.id}`); this.columns = this.columns.filter(c => c.id !== column.id); this.showToast('컬럼이 삭제되었습니다.'); this.loadActivities(); } catch (e) { this.handleApiError(e, '컬럼 삭제에 실패했습니다.'); } },

                openAddCard(column) { this.currentColumn = column; this.cardForm = { id: null, title: '', description: '', priority: 'medium', due_date: '', assigned_user_id: '', column_id: column.id }; this.formErrors = {}; this.showCardModal = true; },
                openEditCard(card) { this.showDetailModal = false; this.cardForm = { id: card.id, title: card.title, description: card.description ?? '', priority: card.priority ?? 'medium', due_date: card.due_date ?? '', assigned_user_id: card.assigned_user_id ?? '', column_id: card.column_id }; this.formErrors = {}; this.currentColumn = this.columns.find(c => c.id === card.column_id); this.showCardModal = true; },
                openCardDetail(card, column) { this.detailCard = card; this.detailColumn = column; this.cardComments = []; this.newComment = ''; this.showDetailModal = true; this.loadComments(card.id); },
                async saveCard() {
                    if (!this.cardForm.title.trim()) return;
                    this.loading = true; this.formErrors = {};
                    const data = { title: this.cardForm.title.trim(), description: this.cardForm.description || null, priority: this.cardForm.priority, due_date: this.cardForm.due_date || null, assigned_user_id: this.cardForm.assigned_user_id || null };
                    try {
                        if (this.cardForm.id) { const res = await this.apiPut(`${apiBase}/cards/${this.cardForm.id}`, data); const col = this.columns.find(c => c.id === this.cardForm.column_id); if (col) { const idx = col.cards.findIndex(c => c.id === this.cardForm.id); if (idx !== -1) col.cards[idx] = { ...col.cards[idx], ...res.data }; } this.showToast('카드가 수정되었습니다.'); }
                        else { const col = this.currentColumn; const res = await this.apiPost(`${apiBase}/columns/${col.id}/cards`, data); col.cards.push(res.data); this.showToast('카드가 추가되었습니다.'); }
                        this.showCardModal = false; this.loadActivities();
                    } catch (e) { if (e.response?.data?.errors) { this.formErrors = {}; for (const [key, msgs] of Object.entries(e.response.data.errors)) this.formErrors[key] = msgs[0]; } else { this.handleApiError(e, '카드 저장에 실패했습니다.'); } }
                    this.loading = false;
                },
                async deleteCard(card) { if (!confirm('이 카드를 삭제하시겠습니까?')) return; try { await this.apiDelete(`${apiBase}/cards/${card.id}`); const col = this.columns.find(c => c.id === card.column_id); if (col) col.cards = col.cards.filter(c => c.id !== card.id); this.showDetailModal = false; this.showToast('카드가 삭제되었습니다.'); this.loadActivities(); } catch (e) { this.handleApiError(e, '카드 삭제에 실패했습니다.'); } },

                priorityClass(p) { return { urgent:'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300', high:'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300', medium:'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300', low:'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' }[p] ?? 'bg-gray-100 text-gray-700'; },
                formatDate(d) { if(!d) return ''; const dt=new Date(d+'T00:00:00'); return `${dt.getMonth()+1}/${dt.getDate()}`; },
                showToast(message, type='success') { const id = ++this.toastId; this.toasts.push({ id, message, type, removing: false }); setTimeout(() => { const t = this.toasts.find(t => t.id === id); if (t) t.removing = true; setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 300); }, 3000); },
                handleApiError(e, fallbackMsg) { console.error(e); if (e.response?.status === 422 && e.response.data?.errors) { const firstMsg = Object.values(e.response.data.errors)[0]?.[0]; this.showToast(firstMsg || fallbackMsg, 'error'); } else if (e.response?.status === 403) { this.showToast('권한이 없습니다.', 'error'); } else if (e.response?.status === 429) { this.showToast('요청이 너무 많습니다. 잠시 후 다시 시도하세요.', 'error'); } else if (!navigator.onLine) { this.showToast('네트워크 연결을 확인해주세요.', 'error'); } else { this.showToast(e.response?.data?.message || fallbackMsg, 'error'); } },
                async apiPost(u,d) { return (await axios.post(u,d)).data; },
                async apiPut(u,d) { return (await axios.put(u,d)).data; },
                async apiDelete(u) { return (await axios.delete(u)).data; },
            };
        }
        document.addEventListener('alpine:init', () => {
            Alpine.store('wsConnected', false);
            Alpine.data('kanbanBoard', kanbanBoard);
            Alpine.data('notificationBell', notificationBell);
        });
    </script>
    @endpush
</x-app-layout>
