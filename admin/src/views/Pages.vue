<template>
  <div>
    <!-- Подтверждение удаления страницы -->
    <div v-if="deleteConfirmPage" class="fixed top-12 left-1/2 transform -translate-x-1/2 z-[9999] bg-white border-2 border-red-300 rounded-lg shadow-xl p-4 min-w-[300px]">
      <div class="flex items-center gap-3 mb-3">
        <div class="flex-shrink-0 w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-red-600">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
          </svg>
        </div>
        <div class="flex-1">
          <h3 class="font-medium text-slate-900">Подтвердите удаление</h3>
          <p class="text-sm text-slate-600 mt-1">
            Вы уверены, что хотите удалить страницу <strong>"{{ deleteConfirmPage.title }}"</strong>?
          </p>
        </div>
      </div>
      <div class="flex gap-2 justify-end">
        <Button variant="secondary" size="sm" @click="cancelDelete">Отмена</Button>
        <Button variant="primary" size="sm" @click="confirmDelete" class="bg-red-600 hover:bg-red-700 text-white">
          Удалить
        </Button>
      </div>
    </div>

    <div v-if="deletePermanentConfirm" class="fixed top-12 left-1/2 transform -translate-x-1/2 z-[9999] bg-white border-2 border-red-500 rounded-lg shadow-xl p-4 min-w-[300px]">
      <div class="flex items-center gap-3 mb-3">
        <div class="flex-shrink-0 w-10 h-10 bg-red-200 rounded-full flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-red-700">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
          </svg>
        </div>
        <div class="flex-1">
          <h3 class="font-medium text-slate-900">Внимание! Удаление навсегда</h3>
          <p class="text-sm text-slate-600 mt-1">
            Вы действительно хотите навсегда удалить страницу <strong>"{{ deletePermanentConfirm.title }}"</strong>? Это действие нельзя отменить.
          </p>
        </div>
      </div>
      <div class="flex gap-2 justify-end">
        <Button variant="secondary" size="sm" @click="cancelDeletePermanent">Отмена</Button>
        <Button variant="primary" size="sm" @click="deleteFromTrashPermanent" class="bg-red-700 hover:bg-red-800 text-white">
          Удалить навсегда
        </Button>
      </div>
    </div>

    <div v-if="bulkConfirmOpen" class="fixed top-12 left-0 right-0 bg-yellow-100 text-yellow-800 p-3 z-50 border-b border-yellow-300 flex items-center justify-between shadow-md">
      <span>Применить настройки к <strong>{{ bulk.selectedPageIds.length }}</strong> выбранным страницам?</span>
      <div class="flex gap-2">
        <Button variant="primary" size="sm" @click="confirmBulk">Да, применить</Button>
        <Button variant="secondary" size="sm" @click="bulkConfirmOpen = false">Отмена</Button>
      </div>
    </div>
    <h3 class="text-lg font-medium mb-2">Страницы (CPT: multiregional_page)</h3>
    
    <!-- Вкладки -->
    <div class="mb-4 border-b border-slate-200">
      <nav class="-mb-px flex space-x-8">
        <button
          @click="activeTab = 'pages'"
          :class="activeTab === 'pages' ? 'border-blue-500 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
          class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
        >
          Все страницы
        </button>
        <button
          v-if="draftItems.length > 0"
          @click="activeTab = 'draft'"
          :class="activeTab === 'draft' ? 'border-blue-500 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
          class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
        >
          Черновики
          <span class="ml-2 bg-slate-200 text-slate-700 rounded-full px-2 py-0.5 text-xs">
            {{ draftItems.length }}
          </span>
        </button>
        <button
          v-if="pendingItems.length > 0"
          @click="activeTab = 'pending'"
          :class="activeTab === 'pending' ? 'border-blue-500 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
          class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
        >
          Ожидают
          <span class="ml-2 bg-slate-200 text-slate-700 rounded-full px-2 py-0.5 text-xs">
            {{ pendingItems.length }}
          </span>
        </button>
        <button
          v-if="futureItems.length > 0"
          @click="activeTab = 'future'"
          :class="activeTab === 'future' ? 'border-blue-500 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
          class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
        >
          Запланировано
          <span class="ml-2 bg-slate-200 text-slate-700 rounded-full px-2 py-0.5 text-xs">
            {{ futureItems.length }}
          </span>
        </button>
        <button
          v-if="trashItems.length > 0"
          @click="activeTab = 'trash'"
          :class="activeTab === 'trash' ? 'border-red-400 text-red-600' : 'border-transparent text-red-400 hover:text-red-600 hover:border-red-300'"
          class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
        >
          Корзина
          <span class="ml-2 bg-red-100 text-red-700 rounded-full px-2 py-0.5 text-xs">
            {{ trashItems.length }}
          </span>
        </button>
      </nav>
    </div>

    <div v-if="activeTab === 'pages'" class="mb-4 flex items-center gap-2">
      <Button variant="primary" @click="openCreateModal">Добавить</Button>
      <a :href="newUrl" class="text-slate-600 underline" target="_blank">Добавить в WP</a>
      <a :href="listUrl" class="text-slate-600 underline" target="_blank">Открыть список в WP</a>
    </div>

    <!-- Поиск и фильтры -->
    <div v-if="activeTab === 'pages'" class="mb-4 flex items-end gap-2">
      <div class="flex-1">
        <Input 
          v-model="tableSearchQuery" 
          placeholder="Поиск..."
          class="w-full"
        />
      </div>
      <div class="flex gap-2">
        <Button variant="secondary" @click="openBulkModal">Массовые настройки</Button>
        <Button @click="filtersModalOpen = true">
          Фильтры
          <span v-if="tableFilterFolders.length > 0 || tableFilterSubdomains.length > 0" class="ml-1 text-xs">
            ({{ tableFilterFolders.length + tableFilterSubdomains.length }})
          </span>
        </Button>
      </div>
    </div>
    <div v-if="activeTab === 'pages'" class="mb-4 text-xs text-slate-500">
      Найдено: {{ tableFilteredItems.length }} из {{ items.length }}
    </div>

    <!-- Попап фильтров -->
    <Modal 
      v-model="filtersModalOpen"
      title="Фильтры"
      size="lg"
    >
      <template #header-actions>
        <Button variant="ghost" size="sm" @click="resetFilters">Сбросить фильтры</Button>
      </template>
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <div class="flex items-center justify-between mb-2">
              <div class="text-slate-600 font-medium">Папки</div>
              <Button 
                variant="ghost" 
                size="sm"
                @click="toggleAllTableFolders"
                class="text-xs"
              >
                {{ allTableFoldersSelected ? 'Снять все' : 'Выбрать все' }}
              </Button>
            </div>
            <Input 
              v-model="tableFolderSearch" 
              placeholder="Поиск папок..." 
              size="sm"
              class="w-full mb-2"
            />
            <div class="text-xs text-slate-500 mb-1">
              Показано: {{ filteredTableFolders.length }} из {{ allFolders.length }}
            </div>
            <div class="border rounded p-2" style="height: 100px; overflow-y: auto;">
              <label v-for="f in filteredTableFolders" :key="f.id" class="block mb-1">
                <input 
                  type="checkbox" 
                  :value="Number(f.id)" 
                  v-model="tableFilterFolders" 
                  @change="handleFolderCheckboxChange"
                /> <span v-html="highlightText(f.slug, tableFolderSearch)"></span>
              </label>
              <div v-if="filteredTableFolders.length === 0" class="text-slate-500 text-xs">Не найдено</div>
            </div>
          </div>
          <div>
            <div class="flex items-center justify-between mb-2">
              <div class="text-slate-600 font-medium">Поддомены</div>
              <Button 
                variant="ghost" 
                size="sm"
                @click="toggleAllTableSubdomains"
                class="text-xs"
              >
                {{ allTableSubdomainsSelected ? 'Снять все' : 'Выбрать все' }}
              </Button>
            </div>
            <Input 
              v-model="tableSubdomainSearch" 
              placeholder="Поиск поддоменов..." 
              size="sm"
              class="w-full mb-2"
            />
            <div class="text-xs text-slate-500 mb-1">
              Показано: {{ filteredTableSubdomains.length }} из {{ allSubdomains.length }}
            </div>
            <div class="border rounded p-2" style="height: 100px; overflow-y: auto;">
              <label v-for="s in filteredTableSubdomains" :key="s.id" class="block mb-1">
                <input 
                  type="checkbox" 
                  :value="Number(s.id)" 
                  v-model="tableFilterSubdomains" 
                  @change="handleSubdomainCheckboxChange"
                /> <span v-html="highlightText(s.slug, tableSubdomainSearch)"></span>
              </label>
              <div v-if="filteredTableSubdomains.length === 0" class="text-slate-500 text-xs">Не найдено</div>
            </div>
          </div>
        </div>
        <div class="flex gap-2 justify-end">
          <Button variant="secondary" @click="closeFiltersModal">Закрыть</Button>
        </div>
    </Modal>

    <div v-if="error" class="text-red-700 mb-2">{{ error }}</div>
    <div v-if="activeTab === 'pages'" class="overflow-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left border-b">
            <th class="py-2 pr-4 w-8">
              <input type="checkbox" :checked="allSelected" @change="toggleSelectAll" />
            </th>
            <th class="py-2 pr-4">Название</th>
            <th class="py-2 pr-4">Папки</th>
            <th class="py-2 pr-4">Поддомены</th>
            <th class="py-2 pr-4">URL</th>
            <th class="py-2 pr-4"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in displayedItems" :key="p.id" class="border-b align-top">
            <td class="py-2 pr-4">
              <input type="checkbox" :value="p.id" v-model="selectedIds" />
            </td>
            <td class="py-2 pr-4">
              <div class="relative">
                <button 
                  @click.stop="showEditMenu(p, $event)"
                  :data-edit-menu-button="p.id"
                  :data-page-id="p.id"
                  class="underline hover:text-blue-600 cursor-pointer"
                  v-html="highlightText(p.title, tableSearchQuery)"
                ></button>
                <div 
                  v-if="editMenuId === p.id" 
                  :data-edit-menu-id="p.id"
                  class="absolute z-50 mt-1 bg-white border rounded shadow-lg p-2 min-w-[200px]"
                  @click.stop
                >
                  <button 
                    @click="editInWp(p)"
                    class="block w-full text-left px-3 py-2 hover:bg-slate-100 rounded text-sm"
                  >
                    📝 Редактировать в WordPress
                  </button>
                  <button 
                    @click="editInOurForm(p)"
                    class="block w-full text-left px-3 py-2 hover:bg-slate-100 rounded text-sm"
                  >
                    ✏️ Редактировать в нашей форме
                  </button>
                  <button 
                    @click="editMenuId = null; document.removeEventListener('click', closeEditMenuOnOutsideClick, true);"
                    class="block w-full text-left px-3 py-2 hover:bg-slate-100 rounded text-sm text-slate-500"
                  >
                    Отмена
                  </button>
                </div>
              </div>
              <div class="text-slate-500" v-html="highlightText(p.slug, tableSearchQuery)"></div>
            </td>
            <td class="py-2 pr-4">
              <div v-if="p.visibility?.folders?.length" v-html="highlightText(mapFolderIdsToSlugs(p.visibility.folders, p.folders).join(', '), tableSearchQuery)"></div>
              <div v-else class="text-slate-500">—</div>
            </td>
            <td class="py-2 pr-4">
              <div v-if="p.visibility?.subdomains?.length" v-html="highlightText(mapSubIdsToSlugs(p.visibility.subdomains, p.subdomains).join(', '), tableSearchQuery)"></div>
              <div v-else class="text-slate-500">—</div>
            </td>
            <td class="py-2 pr-4">
              <div v-if="p.urls?.length">
                <div v-for="u in p.urls" :key="u">
                  <a :href="u" target="_blank" class="underline" v-html="highlightText(u, tableSearchQuery)"></a>
                </div>
              </div>
              <div v-else class="text-slate-500">—</div>
            </td>
            <td class="py-2 pr-4">
              <div class="flex items-center gap-2">
                <a 
                  :href="editUrl(p.id)"
                  target="_blank"
                  class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded transition-colors"
                  title="Редактировать в WordPress"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                  </svg>
                </a>
                <button 
                  @click="editInOurForm(p)"
                  class="p-1.5 text-slate-600 hover:text-slate-800 hover:bg-slate-50 rounded transition-colors"
                  title="Редактировать в форме плагина"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                  </svg>
                </button>
                <button 
                  @click="showDeleteConfirm(p)"
                  class="p-1.5 text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition-colors"
                  title="Удалить страницу"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Показать ещё -->
    <ShowMorePagination
      v-if="activeTab === 'pages'"
      :displayed="displayedItems.length"
      :total="tableFilteredItems.length"
      :has-more="hasMoreItems"
      :items-per-page="itemsPerPage"
      @show-more="currentPage = Math.min(totalPages, currentPage + 1)"
      @update:items-per-page="itemsPerPage = $event"
    />

    <!-- Корзина -->
    <div v-if="activeTab === 'trash'">
      <div v-if="trashLoading" class="text-slate-500 py-4">Загрузка...</div>
      <div v-else-if="trashItems.length === 0" class="text-slate-500 py-4">Корзина пуста</div>
      <div v-else>
        <div class="mb-4 flex justify-end">
          <Button 
            variant="primary" 
            @click="confirmDeleteAllFromTrash"
            class="bg-red-600 hover:bg-red-700 text-white"
            :disabled="deletingAllFromTrash"
          >
            {{ deletingAllFromTrash ? 'Удаление...' : 'Удалить все' }}
          </Button>
        </div>
        <div class="overflow-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left border-b">
              <th class="py-2 pr-4">Название</th>
              <th class="py-2 pr-4">Слаг</th>
              <th class="py-2 pr-4">Дата удаления</th>
              <th class="py-2 pr-4"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in trashItems" :key="p.id" class="border-b align-top">
              <td class="py-2 pr-4">
                <span class="text-slate-900">{{ p.title }}</span>
              </td>
              <td class="py-2 pr-4">
                <span class="text-slate-500">{{ p.slug }}</span>
              </td>
              <td class="py-2 pr-4">
                <span class="text-slate-500 text-xs">{{ p.date }}</span>
              </td>
              <td class="py-2 pr-4">
                <div class="flex items-center gap-2">
                  <button @click="restorePage(p)" :disabled="restoringPageId === p.id" class="flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded disabled:opacity-50 disabled:cursor-not-allowed" title="Восстановить">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span>{{ restoringPageId === p.id ? 'Восстановление...' : 'Восстановить' }}</span>
                  </button>
                  <button @click="confirmDeleteFromTrash(p)" class="text-red-600 hover:text-red-800" title="Удалить навсегда">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        </div>
      </div>
    </div>

    <!-- Черновики -->
    <div v-if="activeTab === 'draft'">
      <div v-if="draftLoading" class="text-slate-500 py-4">Загрузка...</div>
      <div v-else-if="draftItems.length === 0" class="text-slate-500 py-4">Нет черновиков</div>
      <div v-else class="overflow-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left border-b">
              <th class="py-2 pr-4">Название</th>
              <th class="py-2 pr-4">Слаг</th>
              <th class="py-2 pr-4">Дата изменения</th>
              <th class="py-2 pr-4"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in draftItems" :key="p.id" class="border-b align-top">
              <td class="py-2 pr-4">
                <a :href="editUrl(p.id)" target="_blank" class="text-blue-600 hover:underline">{{ p.title }}</a>
              </td>
              <td class="py-2 pr-4">
                <span class="text-slate-500">{{ p.slug }}</span>
              </td>
              <td class="py-2 pr-4">
                <span class="text-slate-500 text-xs">{{ p.date }}</span>
              </td>
              <td class="py-2 pr-4">
                <div class="flex gap-2 items-center">
                  <button @click="editInOurFormById(p.id)" class="text-blue-600 hover:text-blue-800" title="Редактировать">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button @click="changeStatus(p.id, 'publish')" class="text-green-600 hover:text-green-800" title="Опубликовать">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                  </button>
                  <button @click="confirmDeletePage(p)" class="text-red-600 hover:text-red-800" title="Удалить в корзину">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Ожидают проверки -->
    <div v-if="activeTab === 'pending'">
      <div v-if="pendingLoading" class="text-slate-500 py-4">Загрузка...</div>
      <div v-else-if="pendingItems.length === 0" class="text-slate-500 py-4">Нет страниц, ожидающих проверки</div>
      <div v-else class="overflow-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left border-b">
              <th class="py-2 pr-4">Название</th>
              <th class="py-2 pr-4">Слаг</th>
              <th class="py-2 pr-4">Дата изменения</th>
              <th class="py-2 pr-4"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in pendingItems" :key="p.id" class="border-b align-top">
              <td class="py-2 pr-4">
                <a :href="editUrl(p.id)" target="_blank" class="text-blue-600 hover:underline">{{ p.title }}</a>
              </td>
              <td class="py-2 pr-4">
                <span class="text-slate-500">{{ p.slug }}</span>
              </td>
              <td class="py-2 pr-4">
                <span class="text-slate-500 text-xs">{{ p.date }}</span>
              </td>
              <td class="py-2 pr-4">
                <div class="flex gap-2 items-center">
                  <button @click="editInOurFormById(p.id)" class="text-blue-600 hover:text-blue-800" title="Редактировать">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button @click="changeStatus(p.id, 'publish')" class="text-green-600 hover:text-green-800" title="Опубликовать">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                  </button>
                  <button @click="confirmDeletePage(p)" class="text-red-600 hover:text-red-800" title="Удалить в корзину">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Запланированные -->
    <div v-if="activeTab === 'future'">
      <div v-if="futureLoading" class="text-slate-500 py-4">Загрузка...</div>
      <div v-else-if="futureItems.length === 0" class="text-slate-500 py-4">Нет запланированных страниц</div>
      <div v-else class="overflow-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left border-b">
              <th class="py-2 pr-4">Название</th>
              <th class="py-2 pr-4">Слаг</th>
              <th class="py-2 pr-4">Дата публикации</th>
              <th class="py-2 pr-4"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in futureItems" :key="p.id" class="border-b align-top">
              <td class="py-2 pr-4">
                <a :href="editUrl(p.id)" target="_blank" class="text-blue-600 hover:underline">{{ p.title }}</a>
              </td>
              <td class="py-2 pr-4">
                <span class="text-slate-500">{{ p.slug }}</span>
              </td>
              <td class="py-2 pr-4">
                <span class="text-slate-500 text-xs">{{ p.date }}</span>
              </td>
              <td class="py-2 pr-4">
                <div class="flex gap-2 items-center">
                  <button @click="editInOurFormById(p.id)" class="text-blue-600 hover:text-blue-800" title="Редактировать">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button @click="changeStatus(p.id, 'publish')" class="text-green-600 hover:text-green-800" title="Опубликовать">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                  </button>
                  <button @click="confirmDeletePage(p)" class="text-red-600 hover:text-red-800" title="Удалить в корзину">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>


    <!-- Модальное окно массовых настроек -->
    <Modal 
      v-model="bulkModalOpen"
      title="Массовые настройки видимости"
      size="lg"
    >
        
        <div class="mb-4">
          <label class="text-slate-600 mr-2">Правило:</label>
          <Select v-model="bulk.rule">
            <option value="all">Показывать везде</option>
            <option value="allow">Только выбранные</option>
            <option value="deny">Скрывать выбранные</option>
          </Select>
        </div>
        
        <div v-if="bulk.rule !== 'all'" class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <div class="flex items-center justify-between mb-2">
              <div class="text-slate-600">Папки</div>
              <Button 
                variant="ghost" 
                size="sm"
                @click="toggleAllBulkFolders"
                class="text-xs"
              >
                {{ allBulkFoldersSelected ? 'Снять все' : 'Выбрать все' }}
              </Button>
            </div>
            <Input 
              v-model="searchFolderQuery" 
              placeholder="Поиск папок..." 
              size="sm"
              class="w-full mb-2"
            />
            <div class="text-xs text-slate-500 mb-1">
              Показано: {{ filteredFolders.length }} из {{ allFolders.length }}
            </div>
            <div class="border rounded p-2" style="height: 100px; overflow-y: auto;">
              <label v-for="f in filteredFolders" :key="f.id" class="block mb-1">
                <input type="checkbox" :value="Number(f.id)" v-model="bulk.folders" /> <span v-html="highlightText(f.slug, searchFolderQuery)"></span>
              </label>
              <div v-if="filteredFolders.length === 0" class="text-slate-500 text-xs">Не найдено</div>
            </div>
          </div>
          <div>
            <div class="flex items-center justify-between mb-2">
              <div class="text-slate-600">Поддомены</div>
              <Button 
                variant="ghost" 
                size="sm"
                @click="toggleAllBulkSubdomains"
                class="text-xs"
              >
                {{ allBulkSubdomainsSelected ? 'Снять все' : 'Выбрать все' }}
              </Button>
            </div>
            <Input 
              v-model="searchSubdomainQuery" 
              placeholder="Поиск поддоменов..." 
              size="sm"
              class="w-full mb-2"
            />
            <div class="text-xs text-slate-500 mb-1">
              Показано: {{ filteredSubdomains.length }} из {{ allSubdomains.length }}
            </div>
            <div class="border rounded p-2" style="height: 100px; overflow-y: auto;">
              <label v-for="s in filteredSubdomains" :key="s.id" class="block mb-1">
                <input type="checkbox" :value="Number(s.id)" v-model="bulk.subdomains" /> <span v-html="highlightText(s.slug, searchSubdomainQuery)"></span>
              </label>
              <div v-if="filteredSubdomains.length === 0" class="text-slate-500 text-xs">Не найдено</div>
            </div>
          </div>
        </div>

        <div class="mb-4 border-t pt-4">
          <div class="flex items-center justify-between mb-2">
            <label class="text-slate-600 font-medium">Страницы для применения ({{ bulk.selectedPageIds.length }} выбрано):</label>
            <Button variant="ghost" size="sm" @click="toggleSelectAllInBulk">{{ allBulkPagesSelected ? 'Снять все' : 'Выбрать все' }}</Button>
          </div>
          <Input 
            v-model="searchQuery" 
            placeholder="Поиск по названию..." 
            class="w-full mb-2"
          />
          <div class="text-xs text-slate-500 mb-1">
            Показано: {{ filteredItems.length }} из {{ filteredItemsAll.length }} найдено (всего {{ items.length }})
            <span v-if="filteredItemsAll.length > 10" class="text-orange-600"> — уточните поиск, чтобы увидеть все</span>
          </div>
          <div class="border rounded p-2" style="height: 100px; overflow-y: auto;">
            <label v-for="p in filteredItems" :key="p.id" class="block mb-1 hover:bg-slate-50">
              <input type="checkbox" :value="p.id" v-model="bulk.selectedPageIds" /> <span v-html="highlightText(p.title, searchQuery)"></span> <span class="text-slate-500 text-xs" v-html="'(' + highlightText(p.slug, searchQuery) + ')'"></span>
            </label>
            <div v-if="filteredItems.length === 0" class="text-slate-500 text-xs text-center py-2">Не найдено</div>
          </div>
        </div>

        <div class="flex gap-2 justify-end">
          <Button variant="secondary" @click="closeBulkModal">Отмена</Button>
          <Button variant="primary" @click="applyBulk" :disabled="bulk.selectedPageIds.length === 0">
            Применить к выбранным ({{ bulk.selectedPageIds.length }})
          </Button>
        </div>
    </Modal>

    <!-- Модальное окно создания страницы -->
    <Modal 
      v-model="createModalOpen"
      title="Добавить страницу"
      size="lg"
    >
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Название</label>
          <Input 
            v-model="newPage.title" 
            placeholder="Введите название страницы..."
            class="w-full"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Слаг</label>
          <div class="flex items-center gap-2">
            <Input 
              v-model="newPage.slug" 
              placeholder="page-slug"
              class="flex-1"
              @focus="slugWasManuallyChanged = true"
              @input="slugWasManuallyChanged = true"
            />
            <button 
              @click="regenerateSlug"
              class="p-2 border rounded hover:bg-slate-100 transition-colors"
              title="Регенерировать слаг из названия"
              type="button"
            >
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
              </svg>
            </button>
          </div>
          <p class="text-xs text-slate-500 mt-1">
            Слаг автоматически генерируется из названия при вводе. Используйте кнопку ↻ для перегенерации из текущего названия.
          </p>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Контент</label>
          <textarea 
            v-model="newPage.content" 
            placeholder="Введите содержимое страницы..."
            class="w-full border rounded px-3 py-2 h-32 resize-none"
          ></textarea>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Статус</label>
          <Select v-model="newPage.status" class="w-full">
            <option value="publish">Опубликован</option>
            <option value="draft">Черновик</option>
            <option value="pending">Ожидает проверки</option>
            <option value="future">Запланирован</option>
          </Select>
        </div>

        <div v-if="newPage.status === 'future'">
          <label class="block text-sm font-medium text-slate-700 mb-1">Дата и время публикации</label>
          <input 
            type="datetime-local" 
            v-model="newPage.date"
            class="w-full border rounded px-3 py-2"
            :min="new Date().toISOString().slice(0, 16)"
          />
          <p class="text-xs text-slate-500 mt-1">
            Выберите дату и время, когда страница должна быть опубликована
          </p>
        </div>

        <!-- SEO метаданные -->
        <div class="border-t pt-4">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-medium text-slate-700">SEO метаданные</h4>
            <span v-if="seoInfo && seoInfo.seoPlugin" class="text-xs text-slate-500">
              Синхронизация с {{ seoInfo.seoPlugin.name }}
            </span>
          </div>
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">SEO Title</label>
              <Input 
                v-model="newPage.seoTitle" 
                placeholder="Введите SEO заголовок..."
                class="w-full"
              />
              <p class="text-xs text-slate-500 mt-1">Рекомендуемая длина: 50-60 символов</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">SEO Description</label>
              <textarea 
                v-model="newPage.seoDescription" 
                placeholder="Введите SEO описание..."
                class="w-full border rounded px-3 py-2 h-24 resize-none"
              ></textarea>
              <p class="text-xs text-slate-500 mt-1">Рекомендуемая длина: 150-160 символов</p>
            </div>
          </div>
        </div>

        <div class="border-t pt-4">
          <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-2">Видимость страницы</label>
            <Select v-model="newPage.visibilityRule" class="w-full mb-4">
              <option value="all">Показывать везде</option>
              <option value="allow">Только выбранные</option>
              <option value="deny">Скрывать выбранные</option>
            </Select>
          </div>
          
          <div v-if="newPage.visibilityRule !== 'all'" class="grid grid-cols-2 gap-4">
            <div>
              <div class="flex items-center justify-between mb-2">
                <div class="text-slate-600 font-medium">Папки</div>
                <Button 
                  variant="ghost" 
                  size="sm"
                  @click="toggleAllCreateFolders"
                  class="text-xs"
                >
                  {{ allCreateFoldersSelected ? 'Снять все' : 'Выбрать все' }}
                </Button>
              </div>
              <Input 
                v-model="createFolderSearch" 
                placeholder="Поиск папок..." 
                size="sm"
                class="w-full mb-2"
              />
              <div class="text-xs text-slate-500 mb-1">
                Показано: {{ filteredCreateFolders.length }} из {{ allFolders.length }}
              </div>
              <div class="border rounded p-2" style="height: 100px; overflow-y: auto;">
                <label v-for="f in filteredCreateFolders" :key="f.id" class="block mb-1">
                  <input type="checkbox" :value="Number(f.id)" v-model="newPage.folders" /> <span v-html="highlightText(f.slug, createFolderSearch)"></span>
                </label>
                <div v-if="filteredCreateFolders.length === 0" class="text-slate-500 text-xs">Не найдено</div>
              </div>
            </div>
            <div>
              <div class="flex items-center justify-between mb-2">
                <div class="text-slate-600 font-medium">Поддомены</div>
                <Button 
                  variant="ghost" 
                  size="sm"
                  @click="toggleAllCreateSubdomains"
                  class="text-xs"
                >
                  {{ allCreateSubdomainsSelected ? 'Снять все' : 'Выбрать все' }}
                </Button>
              </div>
              <Input 
                v-model="createSubdomainSearch" 
                placeholder="Поиск поддоменов..." 
                size="sm"
                class="w-full mb-2"
              />
              <div class="text-xs text-slate-500 mb-1">
                Показано: {{ filteredCreateSubdomains.length }} из {{ allSubdomains.length }}
              </div>
              <div class="border rounded p-2" style="height: 100px; overflow-y: auto;">
                <label v-for="s in filteredCreateSubdomains" :key="s.id" class="block mb-1">
                  <input type="checkbox" :value="Number(s.id)" v-model="newPage.subdomains" /> <span v-html="highlightText(s.slug, createSubdomainSearch)"></span>
                </label>
                <div v-if="filteredCreateSubdomains.length === 0" class="text-slate-500 text-xs">Не найдено</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="flex gap-2 justify-end mt-6">
        <Button variant="secondary" @click="closeCreateModal">Отмена</Button>
        <Button variant="primary" @click="createPage" :disabled="!newPage.title || creating">
          {{ creating ? 'Создание...' : 'Создать' }}
        </Button>
      </div>
    </Modal>

    <!-- Модальное окно редактирования страницы -->
    <Modal 
      v-model="editPageModalOpen"
      :title="`Редактирование: ${editPage.title || ''}`"
      size="lg"
    >
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Название</label>
          <Input 
            v-model="editPage.title" 
            placeholder="Введите название страницы..."
            class="w-full"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Слаг</label>
          <div class="flex items-center gap-2">
            <Input 
              v-model="editPage.slug" 
              placeholder="page-slug"
              class="flex-1"
              @focus="editSlugWasManuallyChanged = true"
              @input="editSlugWasManuallyChanged = true"
            />
            <button 
              @click="regenerateEditSlug"
              class="p-2 border rounded hover:bg-slate-100 transition-colors"
              title="Регенерировать слаг из названия"
              type="button"
            >
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
              </svg>
            </button>
          </div>
          <p class="text-xs text-slate-500 mt-1">
            Слаг автоматически генерируется из названия при вводе. Используйте кнопку ↻ для перегенерации из текущего названия.
          </p>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Контент</label>
          <textarea 
            v-model="editPage.content" 
            placeholder="Введите содержимое страницы..."
            class="w-full border rounded px-3 py-2 h-32 resize-none"
          ></textarea>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Статус</label>
          <Select v-model="editPage.status" class="w-full">
            <option value="publish">Опубликован</option>
            <option value="draft">Черновик</option>
            <option value="pending">Ожидает проверки</option>
            <option value="future">Запланирован</option>
          </Select>
        </div>

        <div v-if="editPage.status === 'future'">
          <label class="block text-sm font-medium text-slate-700 mb-1">Дата и время публикации</label>
          <input 
            type="datetime-local" 
            v-model="editPage.date"
            class="w-full border rounded px-3 py-2"
            :min="new Date().toISOString().slice(0, 16)"
          />
          <p class="text-xs text-slate-500 mt-1">
            Выберите дату и время, когда страница должна быть опубликована
          </p>
        </div>

        <!-- SEO метаданные -->
        <div class="border-t pt-4">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-medium text-slate-700">SEO метаданные</h4>
            <span v-if="editSeoInfo && editSeoInfo.seoPlugin" class="text-xs text-slate-500">
              Синхронизация с {{ editSeoInfo.seoPlugin.name }}
            </span>
          </div>
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">SEO Title</label>
              <Input 
                v-model="editPage.seoTitle" 
                placeholder="Введите SEO заголовок..."
                class="w-full"
              />
              <p class="text-xs text-slate-500 mt-1">Рекомендуемая длина: 50-60 символов</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">SEO Description</label>
              <textarea 
                v-model="editPage.seoDescription" 
                placeholder="Введите SEO описание..."
                class="w-full border rounded px-3 py-2 h-24 resize-none"
              ></textarea>
              <p class="text-xs text-slate-500 mt-1">Рекомендуемая длина: 150-160 символов</p>
            </div>
          </div>
        </div>

        <div class="border-t pt-4">
          <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-2">Видимость страницы</label>
            <Select v-model="editPage.visibilityRule" class="w-full mb-4">
              <option value="all">Показывать везде</option>
              <option value="allow">Только выбранные</option>
              <option value="deny">Скрывать выбранные</option>
            </Select>
          </div>
          
          <div v-if="editPage.visibilityRule !== 'all'" class="grid grid-cols-2 gap-4">
            <div>
              <div class="flex items-center justify-between mb-2">
                <div class="text-slate-600 font-medium">Папки</div>
                <Button 
                  variant="ghost" 
                  size="sm"
                  @click="toggleAllEditFolders"
                  class="text-xs"
                >
                  {{ allEditFoldersSelected ? 'Снять все' : 'Выбрать все' }}
                </Button>
              </div>
              <Input 
                v-model="editFolderSearch" 
                placeholder="Поиск папок..." 
                size="sm"
                class="w-full mb-2"
              />
              <div class="text-xs text-slate-500 mb-1">
                Показано: {{ filteredEditFolders.length }} из {{ allFolders.length }}
              </div>
              <div class="border rounded p-2" style="height: 100px; overflow-y: auto;">
                <label v-for="f in filteredEditFolders" :key="f.id" class="block mb-1">
                  <input type="checkbox" :value="Number(f.id)" v-model="editPage.folders" /> <span v-html="highlightText(f.slug, editFolderSearch)"></span>
                </label>
                <div v-if="filteredEditFolders.length === 0" class="text-slate-500 text-xs">Не найдено</div>
              </div>
            </div>
            <div>
              <div class="flex items-center justify-between mb-2">
                <div class="text-slate-600 font-medium">Поддомены</div>
                <Button 
                  variant="ghost" 
                  size="sm"
                  @click="toggleAllEditSubdomains"
                  class="text-xs"
                >
                  {{ allEditSubdomainsSelected ? 'Снять все' : 'Выбрать все' }}
                </Button>
              </div>
              <Input 
                v-model="editSubdomainSearch" 
                placeholder="Поиск поддоменов..." 
                size="sm"
                class="w-full mb-2"
              />
              <div class="text-xs text-slate-500 mb-1">
                Показано: {{ filteredEditSubdomains.length }} из {{ allSubdomains.length }}
              </div>
              <div class="border rounded p-2" style="height: 100px; overflow-y: auto;">
                <label v-for="s in filteredEditSubdomains" :key="s.id" class="block mb-1">
                  <input type="checkbox" :value="Number(s.id)" v-model="editPage.subdomains" /> <span v-html="highlightText(s.slug, editSubdomainSearch)"></span>
                </label>
                <div v-if="filteredEditSubdomains.length === 0" class="text-slate-500 text-xs">Не найдено</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="flex gap-2 justify-end mt-6">
        <Button variant="secondary" @click="closeEditPageModal">Отмена</Button>
        <Button variant="primary" @click="updatePage" :disabled="!editPage.title || updating">
          {{ updating ? 'Сохранение...' : 'Сохранить' }}
        </Button>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { inject, ref, onMounted, onUnmounted, reactive, computed, watch } from 'vue';
import { Button, Input, Select, Modal, ShowMorePagination } from '../components';
import { highlightText } from '../utils/highlight.js';
const boot = inject('boot');
const api = inject('api');
const items = ref([]);
const error = ref('');
const listUrl = `${boot.adminUrl}edit.php?post_type=multiregional_page`;
const newUrl = `${boot.adminUrl}post-new.php?post_type=multiregional_page`;
const editUrl = (id) => `${boot.adminUrl}post.php?post=${id}&action=edit`;
const activeTab = ref('pages');
const trashItems = ref([]);
const trashLoading = ref(false);
const restoringPageId = ref(null);
const draftItems = ref([]);
const draftLoading = ref(false);
const pendingItems = ref([]);
const pendingLoading = ref(false);
const futureItems = ref([]);
const futureLoading = ref(false);
const bulkModalOpen = ref(false);
const searchQuery = ref('');
const selectedIds = ref([]);
const bulk = reactive({ rule: 'allow', folders: [], subdomains: [], selectedPageIds: [] });
const allFolders = ref([]);
const allSubdomains = ref([]);

const tableSearchQuery = ref('');
const tableFilterFolders = ref([]);
const tableFilterSubdomains = ref([]);
const currentPage = ref(1);
const itemsPerPage = ref(10);
const filtersModalOpen = ref(false);
const tableFolderSearch = ref('');
const tableSubdomainSearch = ref('');
const bulkConfirmOpen = ref(false);
const createModalOpen = ref(false);
const creating = ref(false);
const createFolderSearch = ref('');
const createSubdomainSearch = ref('');
const slugWasManuallyChanged = ref(false);
const newPage = reactive({
  title: '',
  slug: '',
  content: '',
  status: 'publish',
  date: '',
  visibilityRule: 'all',
  folders: [],
  subdomains: [],
  seoTitle: '',
  seoDescription: ''
});

const editMenuId = ref(null);
const editPageModalOpen = ref(false);
const editingPage = ref(null);
const updating = ref(false);
const deletingPageId = ref(null);
const deleteConfirmPage = ref(null);
const editPage = reactive({
  id: null,
  title: '',
  slug: '',
  content: '',
  status: 'publish',
  date: '',
  visibilityRule: 'all',
  folders: [],
  subdomains: [],
  seoTitle: '',
  seoDescription: ''
});
const editSeoInfo = ref(null);
const editFolderSearch = ref('');
const editSubdomainSearch = ref('');
const editSlugWasManuallyChanged = ref(false);

const seoInfo = ref(null);

// Функция транслитерации для создания слага
function transliterateToSlug(text) {
  if (!text) return '';
  
  const transliterationMap = {
    'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'yo',
    'ж': 'zh', 'з': 'z', 'и': 'i', 'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm',
    'н': 'n', 'о': 'o', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u',
    'ф': 'f', 'х': 'h', 'ц': 'ts', 'ч': 'ch', 'ш': 'sh', 'щ': 'sch',
    'ъ': '', 'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu', 'я': 'ya',
    'А': 'A', 'Б': 'B', 'В': 'V', 'Г': 'G', 'Д': 'D', 'Е': 'E', 'Ё': 'Yo',
    'Ж': 'Zh', 'З': 'Z', 'И': 'I', 'Й': 'Y', 'К': 'K', 'Л': 'L', 'М': 'M',
    'Н': 'N', 'О': 'O', 'П': 'P', 'Р': 'R', 'С': 'S', 'Т': 'T', 'У': 'U',
    'Ф': 'F', 'Х': 'H', 'Ц': 'Ts', 'Ч': 'Ch', 'Ш': 'Sh', 'Щ': 'Sch',
    'Ъ': '', 'Ы': 'Y', 'Ь': '', 'Э': 'E', 'Ю': 'Yu', 'Я': 'Ya'
  };
  
  let slug = text;
  
  // Транслитерация
  for (const [cyr, lat] of Object.entries(transliterationMap)) {
    slug = slug.replace(new RegExp(cyr, 'g'), lat);
  }
  
  // Удаление всех символов кроме букв, цифр, пробелов и дефисов (с учетом Unicode)
  slug = slug.replace(/[^\p{L}\p{N}\s-]/gu, '');
  
  // Замена пробелов и множественных дефисов на один дефис
  slug = slug.replace(/\s+/g, '-').replace(/-+/g, '-');
  
  // Приведение к нижнему регистру
  slug = slug.toLowerCase();
  
  // Удаление дефисов в начале и конце
  slug = slug.replace(/^-+|-+$/g, '');
  
  return slug;
}

// Автоматическое заполнение слага из названия
watch(() => newPage.title, (newTitle, oldTitle) => {
  // Заполняем слаг только если:
  // 1. Слаг еще не был изменен вручную
  // 2. Название заполнено
  if (!slugWasManuallyChanged.value && newTitle) {
    // Вычисляем слаги
    const newSlug = transliterateToSlug(newTitle);
    const oldSlug = oldTitle ? transliterateToSlug(oldTitle) : '';
    
    // Обновляем слаг если:
    // - слаг пустой, или
    // - старый title был пустой (первый ввод), или
    // - текущий слаг совпадает с транслитерацией старого title (значит он был автоматически сгенерирован)
    if (!newPage.slug || !oldTitle || newPage.slug === oldSlug) {
      newPage.slug = newSlug;
    }
  }
});

// Отслеживание ручного изменения слага происходит через события @focus и @input на Input

onMounted(async () => {
  await load();
  await loadFolders();
  await loadSubdomains();
  // Загружаем все статусы для проверки наличия элементов
  await loadTrash();
  await loadDraft();
  await loadPending();
  await loadFuture();
});

watch(activeTab, (newTab) => {
  if (newTab === 'trash') {
    loadTrash();
  } else if (newTab === 'draft') {
    loadDraft();
  } else if (newTab === 'pending') {
    loadPending();
  } else if (newTab === 'future') {
    loadFuture();
  }
});

onUnmounted(() => {
  // Убираем обработчик закрытия меню, если он был добавлен
  document.removeEventListener('click', closeEditMenuOnOutsideClick, true);
});

const tableFilteredItems = computed(() => {
  let result = items.value;
  
  // Поиск по названию/слагу/папкам/поддоменам
  if (tableSearchQuery.value.trim()) {
    const q = tableSearchQuery.value.toLowerCase();
    result = result.filter(p => {
      // Поиск по title и slug
      if (p.title.toLowerCase().includes(q) || p.slug.toLowerCase().includes(q)) {
        return true;
      }
      // Поиск по папкам
      const pFolders = mapFolderIdsToSlugs(p.visibility?.folders || [], p.folders || []);
      if (pFolders.some(f => f.toLowerCase().includes(q))) {
        return true;
      }
      // Поиск по поддоменам
      const pSubs = mapSubIdsToSlugs(p.visibility?.subdomains || [], p.subdomains || []);
      if (pSubs.some(s => s.toLowerCase().includes(q))) {
        return true;
      }
      // Поиск по URL
      if (p.urls && Array.isArray(p.urls)) {
        if (p.urls.some(u => String(u).toLowerCase().includes(q))) {
          return true;
        }
      }
      return false;
    });
  }
  
  // Фильтр по папкам
  const filterFolders = Array.isArray(tableFilterFolders.value) 
    ? tableFilterFolders.value.filter(f => f !== '' && f !== null && f !== undefined).map(Number) 
    : [];
  if (filterFolders.length > 0) {
    result = result.filter(p => {
      const pFolders = Array.isArray(p.visibility?.folders) ? p.visibility.folders.map(Number) : [];
      return filterFolders.some(fid => pFolders.includes(fid));
    });
  }
  
  // Фильтр по поддоменам
  const filterSubs = Array.isArray(tableFilterSubdomains.value) 
    ? tableFilterSubdomains.value.filter(s => s !== '' && s !== null && s !== undefined).map(Number) 
    : [];
  if (filterSubs.length > 0) {
    result = result.filter(p => {
      const pSubs = Array.isArray(p.visibility?.subdomains) ? p.visibility.subdomains.map(Number) : [];
      return filterSubs.some(sid => pSubs.includes(sid));
    });
  }
  
  return result;
});

const totalPages = computed(() => Math.max(1, Math.ceil(tableFilteredItems.value.length / itemsPerPage.value)));

// Отображаем накопительно ("Показать ещё")
const displayedItems = computed(() => {
  const end = currentPage.value * itemsPerPage.value;
  return tableFilteredItems.value.slice(0, end);
});
const hasMoreItems = computed(() => displayedItems.value.length < tableFilteredItems.value.length);

const allSelected = computed(() => {
  if (displayedItems.value.length === 0) return false;
  return displayedItems.value.every(p => selectedIds.value.includes(p.id));
});
const filteredItemsAll = computed(() => {
  if (!searchQuery.value.trim()) return items.value;
  const q = searchQuery.value.toLowerCase();
  return items.value.filter(p => 
    p.title.toLowerCase().includes(q) || p.slug.toLowerCase().includes(q)
  );
});
const filteredItems = computed(() => {
  return filteredItemsAll.value.slice(0, 10); // Показываем максимум 10 результатов
});
const searchFolderQuery = ref('');
const searchSubdomainQuery = ref('');
const filteredFolders = computed(() => {
  if (!searchFolderQuery.value.trim()) return allFolders.value;
  const q = searchFolderQuery.value.toLowerCase();
  return allFolders.value.filter(f => f.slug.toLowerCase().includes(q) || (f.name && f.name.toLowerCase().includes(q)));
});

const filteredSubdomains = computed(() => {
  if (!searchSubdomainQuery.value.trim()) return allSubdomains.value;
  const q = searchSubdomainQuery.value.toLowerCase();
  return allSubdomains.value.filter(s => s.slug.toLowerCase().includes(q) || (s.name && s.name.toLowerCase().includes(q)));
});

const allBulkFoldersSelected = computed(() => {
  if (!filteredFolders.value.length) return false;
  return filteredFolders.value.every(f => bulk.folders.includes(Number(f.id)));
});

const allBulkSubdomainsSelected = computed(() => {
  if (!filteredSubdomains.value.length) return false;
  return filteredSubdomains.value.every(s => bulk.subdomains.includes(Number(s.id)));
});

const allBulkPagesSelected = computed(() => {
  if (!filteredItems.value.length) return false;
  return filteredItems.value.every(p => bulk.selectedPageIds.includes(p.id));
});

const filteredTableFolders = computed(() => {
  if (!tableFolderSearch.value.trim()) return allFolders.value;
  const q = tableFolderSearch.value.toLowerCase();
  return allFolders.value.filter(f => f.slug.toLowerCase().includes(q) || (f.name && f.name.toLowerCase().includes(q)));
});

const filteredTableSubdomains = computed(() => {
  if (!tableSubdomainSearch.value.trim()) return allSubdomains.value;
  const q = tableSubdomainSearch.value.toLowerCase();
  return allSubdomains.value.filter(s => s.slug.toLowerCase().includes(q) || (s.name && s.name.toLowerCase().includes(q)));
});

const allTableFoldersSelected = computed(() => {
  if (!filteredTableFolders.value.length) return false;
  return filteredTableFolders.value.every(f => tableFilterFolders.value.includes(Number(f.id)));
});

const allTableSubdomainsSelected = computed(() => {
  if (!filteredTableSubdomains.value.length) return false;
  return filteredTableSubdomains.value.every(s => tableFilterSubdomains.value.includes(Number(s.id)));
});

// Фильтрация для попапа создания
const filteredCreateFolders = computed(() => {
  if (!createFolderSearch.value.trim()) return allFolders.value;
  const q = createFolderSearch.value.toLowerCase();
  return allFolders.value.filter(f => 
    (f.slug || '').toLowerCase().includes(q) ||
    (f.nominative || '').toLowerCase().includes(q) ||
    (f.dative || '').toLowerCase().includes(q) ||
    (f.genitive || '').toLowerCase().includes(q)
  );
});

const filteredCreateSubdomains = computed(() => {
  if (!createSubdomainSearch.value.trim()) return allSubdomains.value;
  const q = createSubdomainSearch.value.toLowerCase();
  return allSubdomains.value.filter(s => 
    (s.slug || '').toLowerCase().includes(q) ||
    (s.nominative || '').toLowerCase().includes(q) ||
    (s.dative || '').toLowerCase().includes(q) ||
    (s.genitive || '').toLowerCase().includes(q)
  );
});

const allCreateFoldersSelected = computed(() => {
  if (newPage.visibilityRule === 'all' || !filteredCreateFolders.value.length) return false;
  return filteredCreateFolders.value.every(f => newPage.folders.includes(Number(f.id)));
});

const allCreateSubdomainsSelected = computed(() => {
  if (newPage.visibilityRule === 'all' || !filteredCreateSubdomains.value.length) return false;
  return filteredCreateSubdomains.value.every(s => newPage.subdomains.includes(Number(s.id)));
});

async function loadFolders() {
  try {
    const folders = await api.getFolders();
    allFolders.value = folders || [];
  } catch (e) {
    console.error('Failed to load folders:', e);
    allFolders.value = [];
  }
}

async function loadSubdomains() {
  try {
    const subdomains = await api.getSubdomains();
    allSubdomains.value = subdomains || [];
  } catch (e) {
    console.error('Failed to load subdomains:', e);
    allSubdomains.value = [];
  }
}

async function load() {
  try {
    const res = await fetch(`${boot.restUrl}mr-ml/v1/pages`, { headers: { 'X-WP-Nonce': boot.nonce } });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const rows = await res.json();
    rows.forEach(r => {
      if (!r.folders) r.folders = [];
      if (!r.subdomains) r.subdomains = [];
      r._draft = {
        rule: r.visibility?.rule || 'allow',
        folders: Array.isArray(r.visibility?.folders) ? r.visibility.folders.map(Number) : [],
        subdomains: Array.isArray(r.visibility?.subdomains) ? r.visibility.subdomains.map(Number) : [],
      };
    });
    items.value = rows;
    // Загружаем папки и поддомены из данных страниц, если они есть
    if (rows.length > 0 && (!allFolders.value.length || !allSubdomains.value.length)) {
      allFolders.value = rows[0].folders || [];
      allSubdomains.value = rows[0].subdomains || [];
    }
  } catch (e) {
    error.value = String(e.message || e);
  }
}

async function loadTrash() {
  trashLoading.value = true;
  try {
    const res = await fetch(`${boot.restUrl}wp/v2/multiregional_page?status=trash&per_page=100`, { 
      headers: { 'X-WP-Nonce': boot.nonce } 
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const rows = await res.json();
    trashItems.value = rows.map(p => ({
      id: p.id,
      title: p.title?.rendered || p.title?.raw || '',
      slug: p.slug || '',
      date: p.modified ? new Date(p.modified).toLocaleDateString('ru-RU') : ''
    }));
  } catch (e) {
    error.value = 'Ошибка загрузки корзины: ' + String(e.message || e);
    trashItems.value = [];
  } finally {
    trashLoading.value = false;
  }
}

async function loadDraft() {
  draftLoading.value = true;
  try {
    const res = await fetch(`${boot.restUrl}wp/v2/multiregional_page?status=draft&per_page=100`, { 
      headers: { 'X-WP-Nonce': boot.nonce } 
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const rows = await res.json();
    draftItems.value = rows.map(p => ({
      id: p.id,
      title: p.title?.rendered || p.title?.raw || '',
      slug: p.slug || '',
      date: p.modified ? new Date(p.modified).toLocaleDateString('ru-RU') : ''
    }));
  } catch (e) {
    console.error('Ошибка загрузки черновиков:', e);
    draftItems.value = [];
  } finally {
    draftLoading.value = false;
  }
}

async function loadPending() {
  pendingLoading.value = true;
  try {
    const res = await fetch(`${boot.restUrl}wp/v2/multiregional_page?status=pending&per_page=100`, { 
      headers: { 'X-WP-Nonce': boot.nonce } 
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const rows = await res.json();
    pendingItems.value = rows.map(p => ({
      id: p.id,
      title: p.title?.rendered || p.title?.raw || '',
      slug: p.slug || '',
      date: p.modified ? new Date(p.modified).toLocaleDateString('ru-RU') : ''
    }));
  } catch (e) {
    console.error('Ошибка загрузки ожидающих:', e);
    pendingItems.value = [];
  } finally {
    pendingLoading.value = false;
  }
}

async function loadFuture() {
  futureLoading.value = true;
  try {
    const res = await fetch(`${boot.restUrl}wp/v2/multiregional_page?status=future&per_page=100`, { 
      headers: { 'X-WP-Nonce': boot.nonce } 
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const rows = await res.json();
    futureItems.value = rows.map(p => ({
      id: p.id,
      title: p.title?.rendered || p.title?.raw || '',
      slug: p.slug || '',
      date: p.modified ? new Date(p.modified).toLocaleDateString('ru-RU') : ''
    }));
  } catch (e) {
    console.error('Ошибка загрузки запланированных:', e);
    futureItems.value = [];
  } finally {
    futureLoading.value = false;
  }
}

async function restorePage(page) {
  if (restoringPageId.value) return;
  restoringPageId.value = page.id;
  try {
    await api.restorePage(page.id);
    showMessage(`Страница "${page.title}" восстановлена`, 'success');
    await load();
    // Обновляем все списки статусов после восстановления
    await loadTrash();
    await loadDraft();
    await loadPending();
    await loadFuture();
  } catch (e) {
    showMessage('Ошибка восстановления страницы: ' + String(e.message || e), 'error');
  } finally {
    restoringPageId.value = null;
  }
}

function mapFolderIdsToSlugs(ids, folders){
  const map = new Map((folders||[]).map(f => [Number(f.id), f.slug]));
  return (ids||[]).map(id => map.get(Number(id))).filter(Boolean);
}
function mapSubIdsToSlugs(ids, subs){
  const map = new Map((subs||[]).map(s => [Number(s.id), s.slug]));
  return (ids||[]).map(id => map.get(Number(id))).filter(Boolean);
}

// Используем функцию подсветки из утилит

function toggleSelectAll() {
  if (allSelected.value) {
    // Удаляем только текущие страницы из пагинации
    displayedItems.value.forEach(p => {
      const idx = selectedIds.value.indexOf(p.id);
      if (idx >= 0) selectedIds.value.splice(idx, 1);
    });
  } else {
    // Добавляем текущие страницы из пагинации
    displayedItems.value.forEach(p => {
      if (!selectedIds.value.includes(p.id)) {
        selectedIds.value.push(p.id);
      }
    });
  }
}

// Сбрасываем страницу при изменении фильтров или количества на странице
watch([tableSearchQuery, tableFilterFolders, tableFilterSubdomains, itemsPerPage], () => {
  currentPage.value = 1;
});

function handleFolderCheckboxChange(event) {
  // v-model автоматически обрабатывает toggle, функция нужна только для дополнительной логики если потребуется
  // Сбрасываем страницу при изменении фильтра
  currentPage.value = 1;
}

function handleSubdomainCheckboxChange(event) {
  // v-model автоматически обрабатывает toggle, функция нужна только для дополнительной логики если потребуется
  // Сбрасываем страницу при изменении фильтра
  currentPage.value = 1;
}

function toggleAllTableFolders() {
  const filteredIds = filteredTableFolders.value.map(f => Number(f.id));
  if (allTableFoldersSelected.value) {
    // Снимаем только отфильтрованные
    tableFilterFolders.value = tableFilterFolders.value.filter(id => !filteredIds.includes(id));
  } else {
    // Добавляем отфильтрованные, не дублируя
    filteredIds.forEach(id => {
      if (!tableFilterFolders.value.includes(id)) {
        tableFilterFolders.value.push(id);
      }
    });
  }
}

function toggleAllTableSubdomains() {
  const filteredIds = filteredTableSubdomains.value.map(s => Number(s.id));
  if (allTableSubdomainsSelected.value) {
    // Снимаем только отфильтрованные
    tableFilterSubdomains.value = tableFilterSubdomains.value.filter(id => !filteredIds.includes(id));
  } else {
    // Добавляем отфильтрованные, не дублируя
    filteredIds.forEach(id => {
      if (!tableFilterSubdomains.value.includes(id)) {
        tableFilterSubdomains.value.push(id);
      }
    });
  }
}

function closeFiltersModal() {
  filtersModalOpen.value = false;
  tableFolderSearch.value = '';
  tableSubdomainSearch.value = '';
}



function toggleAllBulkFolders() {
  const filteredIds = filteredFolders.value.map(f => Number(f.id));
  if (allBulkFoldersSelected.value) {
    // Снимаем только отфильтрованные
    bulk.folders = bulk.folders.filter(id => !filteredIds.includes(id));
  } else {
    // Добавляем отфильтрованные, не дублируя
    filteredIds.forEach(id => {
      if (!bulk.folders.includes(id)) {
        bulk.folders.push(id);
      }
    });
  }
}

function toggleAllBulkSubdomains() {
  const filteredIds = filteredSubdomains.value.map(s => Number(s.id));
  if (allBulkSubdomainsSelected.value) {
    // Снимаем только отфильтрованные
    bulk.subdomains = bulk.subdomains.filter(id => !filteredIds.includes(id));
  } else {
    // Добавляем отфильтрованные, не дублируя
    filteredIds.forEach(id => {
      if (!bulk.subdomains.includes(id)) {
        bulk.subdomains.push(id);
      }
    });
  }
}

function toggleSelectAllInBulk() {
  const filteredIds = filteredItems.value.map(p => p.id);
  if (allBulkPagesSelected.value) {
    // Снимаем только отфильтрованные
    bulk.selectedPageIds = bulk.selectedPageIds.filter(id => !filteredIds.includes(id));
  } else {
    // Добавляем отфильтрованные, не дублируя
    filteredIds.forEach(id => {
      if (!bulk.selectedPageIds.includes(id)) {
        bulk.selectedPageIds.push(id);
      }
    });
  }
}

function openBulkModal() {
  bulk.selectedPageIds = selectedIds.value.length > 0 ? [...selectedIds.value] : [];
  searchQuery.value = '';
  searchFolderQuery.value = '';
  searchSubdomainQuery.value = '';
  bulkModalOpen.value = true;
}

function closeBulkModal() {
  bulkModalOpen.value = false;
  bulk.selectedPageIds = [];
  searchQuery.value = '';
  searchFolderQuery.value = '';
  searchSubdomainQuery.value = '';
  bulkConfirmOpen.value = false;
}

function toggleAllCreateFolders() {
  const filteredIds = filteredCreateFolders.value.map(f => Number(f.id));
  if (allCreateFoldersSelected.value) {
    newPage.folders = newPage.folders.filter(id => !filteredIds.includes(id));
  } else {
    const existing = new Set(newPage.folders);
    filteredIds.forEach(id => existing.add(id));
    newPage.folders = Array.from(existing);
  }
}

function toggleAllCreateSubdomains() {
  const filteredIds = filteredCreateSubdomains.value.map(s => Number(s.id));
  if (allCreateSubdomainsSelected.value) {
    newPage.subdomains = newPage.subdomains.filter(id => !filteredIds.includes(id));
  } else {
    const existing = new Set(newPage.subdomains);
    filteredIds.forEach(id => existing.add(id));
    newPage.subdomains = Array.from(existing);
  }
}

function showMessage(msg, type = 'success') {
  window.dispatchEvent(new CustomEvent('mr-ml-notify', { detail: { message: msg, type } }));
}

function resetFilters() {
  tableSearchQuery.value = '';
  tableFilterFolders.value = [];
  tableFilterSubdomains.value = [];
  tableFolderSearch.value = '';
  tableSubdomainSearch.value = '';
}

function regenerateSlug() {
  if (newPage.title) {
    newPage.slug = transliterateToSlug(newPage.title);
    slugWasManuallyChanged.value = false; // Разрешаем автообновление после регенерации
  }
}

async function openCreateModal() {
  newPage.title = '';
  newPage.slug = '';
  newPage.content = '';
  newPage.status = 'publish';
  newPage.visibilityRule = 'all';
  newPage.folders = [];
  newPage.subdomains = [];
  newPage.seoTitle = '';
  newPage.seoDescription = '';
  createFolderSearch.value = '';
  createSubdomainSearch.value = '';
  slugWasManuallyChanged.value = false;
  
  // Загружаем информацию о SEO-режиме
  try {
    const seoData = await api.getSeo();
    seoInfo.value = {
      activeSeoPlugin: seoData.activeSeoPlugin || '',
      seoPlugin: seoData.detectedSeoPlugins?.find(p => p.type === seoData.activeSeoPlugin) || null
    };
  } catch (e) {
    console.warn('Failed to load SEO info:', e);
    seoInfo.value = { activeSeoPlugin: '', seoPlugin: null };
  }
  
  createModalOpen.value = true;
}

function closeCreateModal() {
  createModalOpen.value = false;
  newPage.title = '';
  newPage.slug = '';
  newPage.content = '';
  newPage.status = 'publish';
  newPage.date = '';
  newPage.visibilityRule = 'all';
  newPage.folders = [];
  newPage.subdomains = [];
  newPage.seoTitle = '';
  newPage.seoDescription = '';
  createFolderSearch.value = '';
  createSubdomainSearch.value = '';
  slugWasManuallyChanged.value = false;
  seoInfo.value = null;
}

async function createPage() {
  if (!newPage.title || creating.value) return;
  
  // Проверка уникальности слага перед созданием
  if (newPage.slug) {
    const existingSlug = items.value.find(p => p.slug === newPage.slug);
    if (existingSlug) {
      showMessage(`Слаг "${newPage.slug}" уже используется на странице "${existingSlug.title}". Используйте другой слаг.`, 'error');
      creating.value = false;
      return;
    }
  }
  
  creating.value = true;
  try {
    const payload = {
      title: newPage.title,
      slug: newPage.slug || undefined,
      content: newPage.content,
      status: newPage.status || 'publish'
    };
    
    // Если статус 'future' и указана дата, добавляем дату публикации
    if (payload.status === 'future' && newPage.date) {
      // Преобразуем datetime-local в ISO формат для WordPress
      const date = new Date(newPage.date);
      payload.date = date.toISOString();
    }
    
    const response = await api.createPage(payload);
    
    // Устанавливаем видимость, если она была задана
    if (response.id && newPage.visibilityRule !== 'all') {
      try {
        await api.setVisibility(response.id, {
          rule: newPage.visibilityRule,
          folders: newPage.folders,
          subdomains: newPage.subdomains
        });
      } catch (e) {
        console.warn('Failed to set visibility:', e);
      }
    }
    
    // Сохраняем SEO-метаданные (всегда сохраняем, синхронизация произойдет на бэкенде)
    if (response.id && (newPage.seoTitle || newPage.seoDescription)) {
      try {
        await api.setPageSeo(response.id, {
          title: newPage.seoTitle || '',
          description: newPage.seoDescription || ''
        });
      } catch (e) {
        console.warn('Failed to set SEO meta:', e);
      }
    }
    
    showMessage('Страница успешно создана', 'success');
    closeCreateModal();
    await load();
    // Обновляем все списки статусов после создания страницы
    await loadTrash();
    await loadDraft();
    await loadPending();
    await loadFuture();
  } catch (e) {
    showMessage('Ошибка создания страницы: ' + String(e.message || e), 'error');
  } finally {
    creating.value = false;
  }
}


async function applyBulk() {
  if (bulk.selectedPageIds.length === 0) {
    showMessage('Выберите хотя бы одну страницу', 'error');
    return;
  }
  bulkConfirmOpen.value = true;
}

async function confirmBulk() {
  bulkConfirmOpen.value = false;
  
  let saved = 0;
  let errors = 0;
  for (const id of bulk.selectedPageIds) {
    const payload = { rule: bulk.rule, folders: bulk.folders, subdomains: bulk.subdomains };
    const res = await fetch(`${boot.restUrl}mr-ml/v1/visibility/${id}`, {
      method: 'POST',
      headers: { 'X-WP-Nonce': boot.nonce, 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    if (res.ok) saved++; else errors++;
  }
  showMessage(`Готово: ${saved} сохранено, ${errors} ошибок`, saved > 0 ? 'success' : 'error');
  closeBulkModal();
  await load();
  selectedIds.value = [];
}

// Меню выбора способа редактирования
function showEditMenu(page, e) {
  e?.stopPropagation();
  // Закрываем предыдущее меню, если открыто другое
  if (editMenuId.value !== page.id) {
    editMenuId.value = page.id;
    // Добавляем обработчик для закрытия при клике вне меню
    // Используем setTimeout, чтобы не закрыть меню сразу же
    setTimeout(() => {
      document.addEventListener('click', closeEditMenuOnOutsideClick, true);
    }, 0);
  } else {
    editMenuId.value = null;
    document.removeEventListener('click', closeEditMenuOnOutsideClick, true);
  }
}

function closeEditMenuOnOutsideClick(e) {
  if (!editMenuId.value) {
    document.removeEventListener('click', closeEditMenuOnOutsideClick, true);
    return;
  }
  
  // Ищем меню и кнопку открытия для текущего ID
  const menuElement = document.querySelector(`[data-edit-menu-id="${editMenuId.value}"]`);
  const buttonElement = document.querySelector(`[data-edit-menu-button][data-page-id="${editMenuId.value}"]`);
  
  // Проверяем, был ли клик внутри меню или на кнопке
  const clickedInsideMenu = menuElement && menuElement.contains(e.target);
  const clickedOnButton = buttonElement && (buttonElement === e.target || buttonElement.contains(e.target));
  
  // Если клик был вне меню и не на кнопке, закрываем
  if (!clickedInsideMenu && !clickedOnButton) {
    editMenuId.value = null;
    document.removeEventListener('click', closeEditMenuOnOutsideClick, true);
  }
}

function editInWp(page) {
  editMenuId.value = null;
  document.removeEventListener('click', closeEditMenuOnOutsideClick, true);
  window.open(editUrl(page.id), '_blank');
}

async function editInOurFormById(pageId) {
  try {
    const pageData = await api.getPage(pageId);
    editInOurForm({
      id: pageData.id,
      title: pageData.title?.rendered || pageData.title?.raw || '',
      slug: pageData.slug || ''
    });
  } catch (e) {
    showMessage('Ошибка загрузки страницы: ' + String(e.message || e), 'error');
  }
}

async function editInOurForm(page) {
  editMenuId.value = null;
  document.removeEventListener('click', closeEditMenuOnOutsideClick, true);
  editingPage.value = page;
  
  try {
    // Загружаем данные страницы
    const pageData = await api.getPage(page.id);
    editPage.id = pageData.id;
    editPage.title = pageData.title?.rendered || '';
    editPage.slug = pageData.slug || '';
    editPage.content = pageData.content?.rendered || '';
    editPage.status = pageData.status || 'publish';
    
    // Загружаем дату публикации (для статуса future)
    if (pageData.status === 'future' && pageData.date) {
      // Преобразуем ISO дату в формат datetime-local (YYYY-MM-DDTHH:mm)
      const date = new Date(pageData.date);
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      const hours = String(date.getHours()).padStart(2, '0');
      const minutes = String(date.getMinutes()).padStart(2, '0');
      editPage.date = `${year}-${month}-${day}T${hours}:${minutes}`;
    } else {
      editPage.date = '';
    }
    
    // Загружаем видимость
    const visibility = await api.getVisibility(page.id);
    editPage.visibilityRule = visibility.rule || 'all';
    editPage.folders = visibility.folders || [];
    editPage.subdomains = visibility.subdomains || [];
    
    // Загружаем SEO
    const seoData = await api.getPageSeo(page.id);
    editPage.seoTitle = seoData.title || '';
    editPage.seoDescription = seoData.description || '';
    
    // Загружаем информацию о SEO-режиме
    const seoSettings = await api.getSeo();
    editSeoInfo.value = {
      activeSeoPlugin: seoSettings.activeSeoPlugin || '',
      seoPlugin: seoSettings.detectedSeoPlugins?.find(p => p.type === seoSettings.activeSeoPlugin) || null
    };
    
    editFolderSearch.value = '';
    editSubdomainSearch.value = '';
    editSlugWasManuallyChanged.value = false;
    
    editPageModalOpen.value = true;
  } catch (e) {
    showMessage('Ошибка загрузки страницы: ' + String(e.message || e), 'error');
  }
}

function closeEditPageModal() {
  editPageModalOpen.value = false;
  editingPage.value = null;
  editPage.id = null;
  editPage.title = '';
  editPage.slug = '';
  editPage.content = '';
  editPage.status = 'publish';
  editPage.date = '';
  editPage.visibilityRule = 'all';
  editPage.folders = [];
  editPage.subdomains = [];
  editPage.seoTitle = '';
  editPage.seoDescription = '';
  editFolderSearch.value = '';
  editSubdomainSearch.value = '';
  editSlugWasManuallyChanged.value = false;
  editSeoInfo.value = null;
}

async function updatePage() {
  if (!editPage.title || updating.value || !editPage.id) return;
  
  updating.value = true;
  try {
    // Обновляем основные данные страницы
    const payload = {
      title: editPage.title,
      slug: editPage.slug || undefined,
      content: editPage.content,
      status: editPage.status || 'publish'
    };
    
    // Если статус 'future' и указана дата, добавляем дату публикации
    if (payload.status === 'future' && editPage.date) {
      // Преобразуем datetime-local в ISO формат для WordPress
      const date = new Date(editPage.date);
      payload.date = date.toISOString();
    }
    
    await api.updatePage(editPage.id, payload);
    
    // Обновляем видимость
    await api.setVisibility(editPage.id, {
      rule: editPage.visibilityRule,
      folders: editPage.folders,
      subdomains: editPage.subdomains
    });
    
    // Сохраняем SEO-метаданные
    if (editPage.seoTitle || editPage.seoDescription) {
      await api.setPageSeo(editPage.id, {
        title: editPage.seoTitle || '',
        description: editPage.seoDescription || ''
      });
    }
    
    showMessage('Страница успешно обновлена', 'success');
    closeEditPageModal();
    await load();
    // Обновляем все списки статусов после изменения статуса
    await loadTrash();
    await loadDraft();
    await loadPending();
    await loadFuture();
  } catch (e) {
    showMessage('Ошибка обновления страницы: ' + String(e.message || e), 'error');
  } finally {
    updating.value = false;
  }
}

function regenerateEditSlug() {
  if (editPage.title) {
    editPage.slug = transliterateToSlug(editPage.title);
    editSlugWasManuallyChanged.value = false;
  }
}

const filteredEditFolders = computed(() => {
  if (!editFolderSearch.value) return allFolders.value;
  const q = editFolderSearch.value.toLowerCase();
  return allFolders.value.filter(f => f.slug.toLowerCase().includes(q) || f.nominative?.toLowerCase().includes(q) || f.dative?.toLowerCase().includes(q) || f.genitive?.toLowerCase().includes(q));
});

const filteredEditSubdomains = computed(() => {
  if (!editSubdomainSearch.value) return allSubdomains.value;
  const q = editSubdomainSearch.value.toLowerCase();
  return allSubdomains.value.filter(s => s.slug.toLowerCase().includes(q) || s.nominative?.toLowerCase().includes(q) || s.dative?.toLowerCase().includes(q) || s.genitive?.toLowerCase().includes(q));
});

const allEditFoldersSelected = computed(() => {
  if (filteredEditFolders.value.length === 0) return false;
  return filteredEditFolders.value.every(f => editPage.folders.includes(Number(f.id)));
});

const allEditSubdomainsSelected = computed(() => {
  if (filteredEditSubdomains.value.length === 0) return false;
  return filteredEditSubdomains.value.every(s => editPage.subdomains.includes(Number(s.id)));
});

function toggleAllEditFolders() {
  const filteredIds = filteredEditFolders.value.map(f => Number(f.id));
  if (allEditFoldersSelected.value) {
    editPage.folders = editPage.folders.filter(id => !filteredIds.includes(id));
  } else {
    const existing = new Set(editPage.folders);
    filteredIds.forEach(id => existing.add(id));
    editPage.folders = Array.from(existing);
  }
}

function toggleAllEditSubdomains() {
  const filteredIds = filteredEditSubdomains.value.map(s => Number(s.id));
  if (allEditSubdomainsSelected.value) {
    editPage.subdomains = editPage.subdomains.filter(id => !filteredIds.includes(id));
  } else {
    const existing = new Set(editPage.subdomains);
    filteredIds.forEach(id => existing.add(id));
    editPage.subdomains = Array.from(existing);
  }
}

// Watch для автослага редактирования
watch(() => editPageModalOpen.value, (isOpen) => {
  if (isOpen && editPage.title) {
    let stopWatch = null;
    stopWatch = watch(() => editPage.title, (newTitle, oldTitle) => {
      if (!editSlugWasManuallyChanged.value) {
        const newSlug = transliterateToSlug(newTitle);
        if (!editPage.slug || editPage.slug === transliterateToSlug(oldTitle || '')) {
          editPage.slug = newSlug;
        }
      }
    });
    editPage._stopWatch = stopWatch;
  } else if (!isOpen && editPage._stopWatch) {
    editPage._stopWatch();
    editPage._stopWatch = null;
  }
});

let deleteEscHandler = null;

function showDeleteConfirm(page) {
  deleteConfirmPage.value = page;
  deletingPageId.value = page.id;
  
  // Добавляем обработчик ESC
  if (!deleteEscHandler) {
    deleteEscHandler = (e) => {
      if (e.key === 'Escape' && deleteConfirmPage.value) {
        cancelDelete();
      }
    };
    document.addEventListener('keydown', deleteEscHandler);
  }
}

function cancelDelete() {
  deleteConfirmPage.value = null;
  deletingPageId.value = null;
  
  // Удаляем обработчик ESC
  if (deleteEscHandler) {
    document.removeEventListener('keydown', deleteEscHandler);
    deleteEscHandler = null;
  }
}

async function confirmDelete() {
  if (!deletingPageId.value || !deleteConfirmPage.value) return;
  
  const pageTitle = deleteConfirmPage.value.title;
  const pageId = deletingPageId.value;
  
  try {
    await api.deletePage(pageId);
    showMessage(`Страница "${pageTitle}" отправлена в корзину`, 'success');
    cancelDelete();
    await load();
    // Обновляем список корзины после удаления
    await loadTrash();
    // Обновляем остальные списки статусов
    await loadDraft();
    await loadPending();
    await loadFuture();
  } catch (e) {
    showMessage('Ошибка удаления страницы: ' + String(e.message || e), 'error');
    cancelDelete();
  }
}

function confirmDeletePage(page) {
  deleteConfirmPage.value = page;
  deletingPageId.value = page.id;
  
  // Добавляем обработчик ESC
  if (!deleteEscHandler) {
    deleteEscHandler = (e) => {
      if (e.key === 'Escape' && deleteConfirmPage.value) {
        cancelDelete();
      }
    };
    document.addEventListener('keydown', deleteEscHandler);
  }
}

const deletePermanentConfirm = ref(null);
const deletingPermanentPageId = ref(null);

function confirmDeleteFromTrash(page) {
  deletePermanentConfirm.value = page;
  deletingPermanentPageId.value = page.id;
}

function cancelDeletePermanent() {
  deletePermanentConfirm.value = null;
  deletingPermanentPageId.value = null;
}

async function deleteFromTrashPermanent() {
  if (!deletingPermanentPageId.value || !deletePermanentConfirm.value) return;
  
  const pageTitle = deletePermanentConfirm.value.title;
  const pageId = deletingPermanentPageId.value;
  
  try {
    await api.deletePage(pageId, true); // force=true для удаления навсегда
    showMessage(`Страница "${pageTitle}" удалена навсегда`, 'success');
    cancelDeletePermanent();
    await load();
    await loadTrash();
    await loadDraft();
    await loadPending();
    await loadFuture();
  } catch (e) {
    showMessage('Ошибка удаления страницы: ' + String(e.message || e), 'error');
    cancelDeletePermanent();
  }
}

async function changeStatus(pageId, newStatus) {
  try {
    await api.updatePage(pageId, { status: newStatus });
    showMessage('Статус страницы изменен', 'success');
    await load();
    await loadTrash();
    await loadDraft();
    await loadPending();
    await loadFuture();
  } catch (e) {
    showMessage('Ошибка изменения статуса: ' + String(e.message || e), 'error');
  }
}

function confirmDeleteAllFromTrash() {
  if (trashItems.value.length === 0) return;
  deleteAllConfirmOpen.value = true;
}

function cancelDeleteAll() {
  deleteAllConfirmOpen.value = false;
}

async function deleteAllFromTrash() {
  if (trashItems.value.length === 0 || deletingAllFromTrash.value) return;
  
  deletingAllFromTrash.value = true;
  let successCount = 0;
  let errorCount = 0;
  
  try {
    for (const page of trashItems.value) {
      try {
        await api.deletePage(page.id, true); // force=true для удаления навсегда
        successCount++;
      } catch (e) {
        errorCount++;
        console.error(`Ошибка удаления страницы ${page.id}:`, e);
      }
    }
    
    if (successCount > 0) {
      showMessage(`Удалено страниц: ${successCount}${errorCount > 0 ? `, ошибок: ${errorCount}` : ''}`, successCount > 0 ? 'success' : 'error');
    } else {
      showMessage('Ошибка удаления страниц', 'error');
    }
    
    cancelDeleteAll();
    await load();
    await loadTrash();
    await loadDraft();
    await loadPending();
    await loadFuture();
  } catch (e) {
    showMessage('Ошибка удаления страниц: ' + String(e.message || e), 'error');
  } finally {
    deletingAllFromTrash.value = false;
  }
}

// Очистка обработчика при размонтировании компонента
onUnmounted(() => {
  if (deleteEscHandler) {
    document.removeEventListener('keydown', deleteEscHandler);
  }
});

</script>