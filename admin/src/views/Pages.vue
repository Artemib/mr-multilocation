<template>
  <div>
    <div v-if="bulkConfirmOpen" class="fixed top-12 left-0 right-0 bg-yellow-100 text-yellow-800 p-3 z-50 border-b border-yellow-300 flex items-center justify-between shadow-md">
      <span>Применить настройки к <strong>{{ bulk.selectedPageIds.length }}</strong> выбранным страницам?</span>
      <div class="flex gap-2">
        <button class="bg-blue-600 text-white px-3 py-1 rounded text-sm" @click="confirmBulk">Да, применить</button>
        <button class="bg-slate-400 text-white px-3 py-1 rounded text-sm" @click="bulkConfirmOpen = false">Отмена</button>
      </div>
    </div>
    <h3 class="text-lg font-medium mb-2">Страницы (CPT: multiregional_page)</h3>
    <div class="flex items-center gap-2 mb-4">
      <a :href="newUrl" class="bg-blue-600 text-white px-3 py-1 rounded" target="_blank">Добавить</a>
      <a :href="listUrl" class="text-slate-600 underline" target="_blank">Открыть список в WP</a>
      <button class="bg-blue-600 text-white px-3 py-1 rounded" @click="openBulkModal">Массовые настройки</button>
    </div>

    <!-- Поиск и фильтры -->
    <div class="mb-4 flex items-center gap-2">
      <div class="flex-1">
        <label class="text-sm text-slate-600 mb-1 block">Поиск:</label>
        <input 
          v-model="tableSearchQuery" 
          type="text" 
          placeholder="Поиск..." 
          class="w-full border rounded px-2 py-1"
        />
      </div>
      <div>
        <label class="text-sm text-slate-600 mb-1 block">&nbsp;</label>
        <button class="bg-blue-600 text-white px-4 py-1 rounded" @click="filtersModalOpen = true">
          Фильтры
          <span v-if="tableFilterFolders.length > 0 || tableFilterSubdomains.length > 0" class="ml-1 text-xs">
            ({{ tableFilterFolders.length + tableFilterSubdomains.length }})
          </span>
        </button>
      </div>
    </div>
    <div class="mb-4 text-xs text-slate-500">
      Найдено: {{ tableFilteredItems.length }} из {{ items.length }}
    </div>

    <!-- Попап фильтров -->
    <div v-if="filtersModalOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="closeFiltersModal">
      <div class="bg-white rounded-lg p-6 max-w-3xl w-full max-h-[90vh] overflow-auto">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold">Фильтры</h3>
          <div class="flex items-center gap-3">
            <button class="text-slate-600 underline text-sm" @click="resetFilters">Сбросить фильтры</button>
            <button class="text-slate-500" @click="closeFiltersModal">✕</button>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <div class="flex items-center justify-between mb-2">
              <div class="text-slate-600 font-medium">Папки</div>
              <button 
                @click="toggleAllTableFolders" 
                class="text-xs text-blue-600 underline"
              >
                {{ allTableFoldersSelected ? 'Снять все' : 'Выбрать все' }}
              </button>
            </div>
            <input 
              v-model="tableFolderSearch" 
              type="text" 
              placeholder="Поиск папок..." 
              class="w-full border rounded px-2 py-1 mb-2 text-sm"
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
              <button 
                @click="toggleAllTableSubdomains" 
                class="text-xs text-blue-600 underline"
              >
                {{ allTableSubdomainsSelected ? 'Снять все' : 'Выбрать все' }}
              </button>
            </div>
            <input 
              v-model="tableSubdomainSearch" 
              type="text" 
              placeholder="Поиск поддоменов..." 
              class="w-full border rounded px-2 py-1 mb-2 text-sm"
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
          <button class="bg-slate-400 text-white px-4 py-2 rounded" @click="closeFiltersModal">Закрыть</button>
        </div>
      </div>
    </div>

    <div v-if="error" class="text-red-700 mb-2">{{ error }}</div>
    <div class="overflow-auto">
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
              <a :href="editUrl(p.id)" target="_blank" class="underline" v-html="highlightText(p.title, tableSearchQuery)"></a>
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
              <button class="bg-slate-600 text-white px-2 py-1 rounded text-xs" @click="openEditModal(p)">Настроить</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Показать ещё -->
    <div class="mt-4 flex items-center justify-between">
      <div class="text-sm text-slate-600">
        Показано: {{ displayedItems.length }} из {{ tableFilteredItems.length }}
      </div>
      <div class="flex flex-col items-center gap-2">
        <button 
          v-if="hasMoreItems"
          class="px-4 py-2 border rounded bg-blue-600 text-white disabled:opacity-50 hover:bg-blue-700" 
          :disabled="!hasMoreItems" 
          @click="currentPage = Math.min(totalPages, currentPage + 1)"
        >
          Показать ещё
        </button>
      </div>
      <div class="text-sm text-slate-600">
        Показывать по: 
        <select v-model.number="itemsPerPage" class="border rounded px-2 py-1">
          <option :value="10">10</option>
          <option :value="25">25</option>
          <option :value="50">50</option>
        </select>
      </div>
    </div>

    <!-- Модальное окно редактирования -->
    <div v-if="editing" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="closeEditModal">
      <div class="bg-white rounded-lg p-6 max-w-2xl w-full max-h-[90vh] overflow-auto">
        <h3 class="text-lg font-semibold mb-4">Настройка видимости: {{ editing.title }}</h3>
        <div class="mb-4">
          <label class="text-slate-600 mr-2">Правило:</label>
          <select v-model="editing._draft.rule" class="border rounded px-2 py-1">
            <option value="all">Показывать везде</option>
            <option value="allow">Только выбранные</option>
            <option value="deny">Скрывать выбранные</option>
          </select>
        </div>
        <div v-if="editing._draft.rule !== 'all'" class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <div class="flex items-center justify-between mb-2">
              <div class="text-slate-600">Папки</div>
              <button 
                @click="toggleAllFolders" 
                class="text-xs text-blue-600 underline"
              >
                {{ allFoldersSelected ? 'Снять все' : 'Выбрать все' }}
              </button>
            </div>
            <input 
              v-model="searchFolderQuery" 
              type="text" 
              placeholder="Поиск папок..." 
              class="w-full border rounded px-2 py-1 mb-2 text-sm"
            />
            <div class="text-xs text-slate-500 mb-1">
              Показано: {{ filteredFolders.length }} из {{ (editing?.folders || allFolders).length }}
            </div>
            <div class="border rounded p-2" style="height: 100px; overflow-y: auto;">
              <label v-for="f in filteredFolders" :key="f.id" class="block mb-1">
                <input type="checkbox" :value="Number(f.id)" v-model="editing._draft.folders" /> <span v-html="highlightText(f.slug, searchFolderQuery)"></span>
              </label>
              <div v-if="filteredFolders.length === 0" class="text-slate-500 text-xs">Не найдено</div>
            </div>
          </div>
          <div>
            <div class="flex items-center justify-between mb-2">
              <div class="text-slate-600">Поддомены</div>
              <button 
                @click="toggleAllSubdomains" 
                class="text-xs text-blue-600 underline"
              >
                {{ allSubdomainsSelected ? 'Снять все' : 'Выбрать все' }}
              </button>
            </div>
            <input 
              v-model="searchSubdomainQuery" 
              type="text" 
              placeholder="Поиск поддоменов..." 
              class="w-full border rounded px-2 py-1 mb-2 text-sm"
            />
            <div class="text-xs text-slate-500 mb-1">
              Показано: {{ filteredSubdomains.length }} из {{ (editing?.subdomains || allSubdomains).length }}
            </div>
            <div class="border rounded p-2" style="height: 100px; overflow-y: auto;">
              <label v-for="s in filteredSubdomains" :key="s.id" class="block mb-1">
                <input type="checkbox" :value="Number(s.id)" v-model="editing._draft.subdomains" /> <span v-html="highlightText(s.slug, searchSubdomainQuery)"></span>
              </label>
              <div v-if="filteredSubdomains.length === 0" class="text-slate-500 text-xs">Не найдено</div>
            </div>
          </div>
        </div>
        <div class="flex gap-2 justify-end">
          <button class="bg-slate-400 text-white px-4 py-2 rounded" @click="closeEditModal">Отмена</button>
          <button class="bg-blue-600 text-white px-4 py-2 rounded" @click="saveVis(editing)">Сохранить</button>
        </div>
      </div>
    </div>

    <!-- Модальное окно массовых настроек -->
    <div v-if="bulkModalOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="closeBulkModal">
      <div class="bg-white rounded-lg p-6 max-w-3xl w-full max-h-[90vh] overflow-auto">
        <h3 class="text-lg font-semibold mb-4">Массовые настройки видимости</h3>
        
        <div class="mb-4">
          <label class="text-slate-600 mr-2">Правило:</label>
          <select v-model="bulk.rule" class="border rounded px-2 py-1">
            <option value="all">Показывать везде</option>
            <option value="allow">Только выбранные</option>
            <option value="deny">Скрывать выбранные</option>
          </select>
        </div>
        
        <div v-if="bulk.rule !== 'all'" class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <div class="flex items-center justify-between mb-2">
              <div class="text-slate-600">Папки</div>
              <button 
                @click="toggleAllBulkFolders" 
                class="text-xs text-blue-600 underline"
              >
                {{ allBulkFoldersSelected ? 'Снять все' : 'Выбрать все' }}
              </button>
            </div>
            <input 
              v-model="searchFolderQuery" 
              type="text" 
              placeholder="Поиск папок..." 
              class="w-full border rounded px-2 py-1 mb-2 text-sm"
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
              <button 
                @click="toggleAllBulkSubdomains" 
                class="text-xs text-blue-600 underline"
              >
                {{ allBulkSubdomainsSelected ? 'Снять все' : 'Выбрать все' }}
              </button>
            </div>
            <input 
              v-model="searchSubdomainQuery" 
              type="text" 
              placeholder="Поиск поддоменов..." 
              class="w-full border rounded px-2 py-1 mb-2 text-sm"
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
            <button class="text-sm text-blue-600 underline" @click="toggleSelectAllInBulk">{{ allBulkPagesSelected ? 'Снять все' : 'Выбрать все' }}</button>
          </div>
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Поиск по названию..." 
            class="w-full border rounded px-2 py-1 mb-2"
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
          <button class="bg-slate-400 text-white px-4 py-2 rounded" @click="closeBulkModal">Отмена</button>
          <button class="bg-blue-600 text-white px-4 py-2 rounded" @click="applyBulk" :disabled="bulk.selectedPageIds.length === 0">
            Применить к выбранным ({{ bulk.selectedPageIds.length }})
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { inject, ref, onMounted, onUnmounted, reactive, computed, watch } from 'vue';
const boot = inject('boot');
const items = ref([]);
const error = ref('');
const listUrl = `${boot.adminUrl}edit.php?post_type=multiregional_page`;
const newUrl = `${boot.adminUrl}post-new.php?post_type=multiregional_page`;
const editUrl = (id) => `${boot.adminUrl}post.php?post=${id}&action=edit`;
const editing = ref(null);
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

onMounted(async () => {
  await load();
  const onKey = (e) => {
    if (e.key === 'Escape') {
      if (filtersModalOpen.value) closeFiltersModal();
      if (bulkModalOpen.value) closeBulkModal();
      if (bulkConfirmOpen.value) bulkConfirmOpen.value = false;
    }
  };
  window.addEventListener('keydown', onKey);
  (window).__mr_ml_pages_onKey = onKey;
});

onUnmounted(() => {
  const onKey = (window).__mr_ml_pages_onKey;
  if (onKey) window.removeEventListener('keydown', onKey);
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
  const source = editing.value && editing.value.folders ? editing.value.folders : allFolders.value;
  if (!searchFolderQuery.value.trim()) return source;
  const q = searchFolderQuery.value.toLowerCase();
  return source.filter(f => f.slug.toLowerCase().includes(q) || (f.name && f.name.toLowerCase().includes(q)));
});
const filteredSubdomains = computed(() => {
  const source = editing.value && editing.value.subdomains ? editing.value.subdomains : allSubdomains.value;
  if (!searchSubdomainQuery.value.trim()) return source;
  const q = searchSubdomainQuery.value.toLowerCase();
  return source.filter(s => s.slug.toLowerCase().includes(q) || (s.name && s.name.toLowerCase().includes(q)));
});

const allFoldersSelected = computed(() => {
  if (!editing.value || !filteredFolders.value.length) return false;
  return filteredFolders.value.every(f => editing.value._draft.folders.includes(Number(f.id)));
});

const allSubdomainsSelected = computed(() => {
  if (!editing.value || !filteredSubdomains.value.length) return false;
  return filteredSubdomains.value.every(s => editing.value._draft.subdomains.includes(Number(s.id)));
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
    if (rows.length > 0) {
      allFolders.value = rows[0].folders || [];
      allSubdomains.value = rows[0].subdomains || [];
    }
  } catch (e) {
    error.value = String(e.message || e);
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

// Функция подсветки совпадений
function highlightText(text, query) {
  if (!query || !text) {
    // Экранируем HTML для безопасности
    return String(text || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  }
  const safeText = String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
  return safeText.replace(regex, '<mark class="bg-yellow-300 font-bold">$1</mark>');
}

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

function openEditModal(p) {
  editing.value = { ...p, _draft: { ...p._draft } };
}

function closeEditModal() {
  editing.value = null;
  searchFolderQuery.value = '';
  searchSubdomainQuery.value = '';
}

function toggleAllFolders() {
  if (!editing.value) return;
  const filteredIds = filteredFolders.value.map(f => Number(f.id));
  if (allFoldersSelected.value) {
    // Снимаем только отфильтрованные
    editing.value._draft.folders = editing.value._draft.folders.filter(id => !filteredIds.includes(id));
  } else {
    // Добавляем отфильтрованные, не дублируя
    filteredIds.forEach(id => {
      if (!editing.value._draft.folders.includes(id)) {
        editing.value._draft.folders.push(id);
      }
    });
  }
}

function toggleAllSubdomains() {
  if (!editing.value) return;
  const filteredIds = filteredSubdomains.value.map(s => Number(s.id));
  if (allSubdomainsSelected.value) {
    // Снимаем только отфильтрованные
    editing.value._draft.subdomains = editing.value._draft.subdomains.filter(id => !filteredIds.includes(id));
  } else {
    // Добавляем отфильтрованные, не дублируя
    filteredIds.forEach(id => {
      if (!editing.value._draft.subdomains.includes(id)) {
        editing.value._draft.subdomains.push(id);
      }
    });
  }
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

async function saveVis(p){
  const payload = { rule: p._draft.rule, folders: p._draft.folders, subdomains: p._draft.subdomains };
  const res = await fetch(`${boot.restUrl}mr-ml/v1/visibility/${p.id}`, {
    method: 'POST',
    headers: { 'X-WP-Nonce': boot.nonce, 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  if (!res.ok) {
    const t = await res.text();
    showMessage(`Ошибка сохранения: ${res.status} ${t}`, 'error');
    return;
  }
  closeEditModal();
  await load();
  showMessage('Видимость страницы успешно сохранена', 'success');
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
</script>