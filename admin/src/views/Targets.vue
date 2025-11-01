<template>
  <div>
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-medium">{{ tabLabel }}</h3>
    </div>
    <Alert v-if="error" type="error">{{ error }}</Alert>
    
    <!-- Поиск и кнопка добавления в один ряд -->
    <div class="mb-4 flex items-center gap-2">
      <Input 
        v-model="searchQuery" 
        placeholder="Поиск..."
        class="flex-1"
      />
      <Button @click="showAddModal = true">Добавить</Button>
    </div>

    <div class="overflow-x-auto">
      <div class="flex items-center justify-between mb-2">
        <div class="text-sm text-slate-600">
          Показано: {{ displayedItems.length }} из {{ filteredItems.length }} (всего: {{ allItems.length }})
        </div>
      </div>
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left border-b">
            <th v-if="isHybrid" class="py-2 pr-4">Тип</th>
            <th class="py-2 pr-4">Слаг</th>
            <th class="py-2 pr-4">Именительный</th>
            <th class="py-2 pr-4">Дательный</th>
            <th class="py-2 pr-4">Родительный</th>
            <th class="py-2 pr-4"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in displayedItems" :key="item.id" class="border-b">
            <td v-if="isHybrid" class="py-2 pr-4">
              <Select 
                :model-value="getCurrentType(item)" 
                @update:model-value="(val) => onTypeChange(item, val)"
                size="sm"
              >
                <option value="both">Везде</option>
                <option value="folder">Папка</option>
                <option value="subdomain">Поддомен</option>
              </Select>
            </td>
            <td class="py-2 pr-4">
              <div v-if="isEditingId !== item.id" 
                   @dblclick="startEdit(item)"
                   class="border rounded px-2 py-1 w-36 bg-slate-100 cursor-pointer min-h-[2rem] flex items-center"
                   v-html="highlightText(item.slug, searchQuery)"></div>
              <input 
                v-else
                v-model="item.slug" 
                @keydown.enter.prevent="commitEdit(item)"
                @blur="commitEdit(item)"
                class="border rounded px-2 py-1 w-36 bg-white"
                autofocus
              />
            </td>
            <td class="py-2 pr-4">
              <div v-if="isEditingId !== item.id" 
                   @dblclick="startEdit(item)"
                   class="border rounded px-2 py-1 w-36 bg-slate-100 cursor-pointer min-h-[2rem] flex items-center"
                   v-html="highlightText(item.nominative, searchQuery)"></div>
              <input 
                v-else
                v-model="item.nominative" 
                @keydown.enter.prevent="commitEdit(item)"
                @blur="commitEdit(item)"
                class="border rounded px-2 py-1 w-36 bg-white"
                autofocus
              />
            </td>
            <td class="py-2 pr-4">
              <div v-if="isEditingId !== item.id" 
                   @dblclick="startEdit(item)"
                   class="border rounded px-2 py-1 w-36 bg-slate-100 cursor-pointer min-h-[2rem] flex items-center"
                   v-html="highlightText(item.dative, searchQuery)"></div>
              <input 
                v-else
                v-model="item.dative" 
                @keydown.enter.prevent="commitEdit(item)"
                @blur="commitEdit(item)"
                class="border rounded px-2 py-1 w-36 bg-white"
                autofocus
              />
            </td>
            <td class="py-2 pr-4">
              <div v-if="isEditingId !== item.id" 
                   @dblclick="startEdit(item)"
                   class="border rounded px-2 py-1 w-36 bg-slate-100 cursor-pointer min-h-[2rem] flex items-center"
                   v-html="highlightText(item.genitive, searchQuery)"></div>
              <input 
                v-else
                v-model="item.genitive" 
                @keydown.enter.prevent="commitEdit(item)"
                @blur="commitEdit(item)"
                class="border rounded px-2 py-1 w-36 bg-white"
                autofocus
              />
            </td>
            <td class="py-2 pr-4">
              <div class="relative inline-block" data-mr-ml-delete-wrap>
                <button 
                  class="text-red-600 hover:text-red-800 text-xl font-bold leading-none" 
                  @click="onDelete(item)"
                  title="Удалить"
                >×</button>
                <div v-if="deleteConfirmId === item.id" class="absolute z-50 -top-2 right-6 translate-y-[-100%]">
                  <div class="bg-yellow-100 text-yellow-900 border border-yellow-300 rounded shadow-md p-3 w-64">
                    <div class="text-sm mb-2">Удалить <strong>{{ item.nominative || item.slug }}</strong>?</div>
                    <div class="flex gap-2 justify-end">
                      <Button variant="danger" size="sm" @click="confirmDelete">Удалить</Button>
                      <Button variant="secondary" size="sm" @click="cancelDelete">Отмена</Button>
                    </div>
                  </div>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <ShowMorePagination
        :displayed="displayedItems.length"
        :total="filteredItems.length"
        :has-more="hasMoreItems"
        :items-per-page="itemsPerPageTargets"
        @show-more="showMore"
        @update:items-per-page="itemsPerPageTargets = $event"
      >
        <template #info>
          Показано: {{ displayedItems.length }} из {{ filteredItems.length }} (всего: {{ allItems.length }})
        </template>
      </ShowMorePagination>
    </div>

    <!-- Модальное окно для добавления -->
    <Modal v-model="showAddModal" title="Добавить элемент" size="md">
      <div class="space-y-4">
        <div v-if="isHybrid">
          <label class="block text-sm font-medium text-slate-700 mb-1">Тип</label>
          <Select v-model="draft.type" class="w-full">
            <option value="both">Везде</option>
            <option value="folder">Папка</option>
            <option value="subdomain">Поддомен</option>
          </Select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Слаг</label>
          <Input v-model="draft.slug" placeholder="Слаг" class="w-full" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Именительный (Москва)</label>
          <Input v-model="draft.nominative" placeholder="Именительный (Москва)" class="w-full" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Дательный (Москве)</label>
          <Input v-model="draft.dative" placeholder="Дательный (Москве)" class="w-full" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Родительный (Москвы)</label>
          <Input v-model="draft.genitive" placeholder="Родительный (Москвы)" class="w-full" />
        </div>
      </div>
      
      <!-- Фиксированные кнопки внутри попапа -->
      <div class="sticky bottom-0 bg-white border-t border-slate-200 -mx-6 px-6 pt-4 pb-6 mt-6 flex gap-2 justify-end">
        <Button variant="secondary" @click="cancelAdd">Отмена</Button>
        <Button variant="success" @click="onCreate">Сохранить</Button>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { inject, reactive, ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { Button, Input, Select, ShowMorePagination, Alert, Modal } from '../components';
import { highlightText } from '../utils/highlight.js';
const api = inject('api');

const mode = ref('hybrid');
const folders = ref([]);
const subdomains = ref([]);
const loading = ref(false);
const draft = reactive({ type: 'both', slug: '', nominative: '', dative: '', genitive: '' });
const error = ref('');
const showAddModal = ref(false);
const deleteConfirmId = ref(null);
const deleteConfirmItem = computed(() => deleteConfirmId.value ? allItems.value.find(i => i.id === deleteConfirmId.value) : null);
const isEditingId = ref(null);
const originalValues = ref({});
const currentPageTargets = ref(1);
const itemsPerPageTargets = ref(10);
const searchQuery = ref('');

const isHybrid = computed(() => mode.value === 'hybrid');
const tabLabel = computed(() => {
  if (mode.value === 'hybrid') return 'Поддомены/Папки';
  if (mode.value === 'folder') return 'Папки';
  if (mode.value === 'subdomain') return 'Поддомены';
  return 'Поддомены/Папки';
});

const allItems = computed(() => {
  const items = [];
  const processedSlugs = new Set();
  
  // Сначала обрабатываем элементы, которые есть и в папках, и в поддоменах (оба имеют одинаковый slug)
  folders.value.forEach(f => {
    const matchingSub = subdomains.value.find(s => s.slug === f.slug);
    if (matchingSub && isHybrid.value) {
      // Элемент везде
      items.push({
        ...f,
        type: 'both',
        source: 'folder',
        subdomainId: matchingSub.id,
      });
      processedSlugs.add(f.slug);
    } else {
      // Только в папках
      items.push({
        ...f,
        type: 'folder',
        source: 'folder',
      });
      processedSlugs.add(f.slug);
    }
  });
  
  // Добавляем поддомены, которых нет в папках
  subdomains.value.forEach(s => {
    if (!processedSlugs.has(s.slug)) {
      items.push({
        ...s,
        type: 'subdomain',
        source: 'subdomain',
      });
    }
  });
  
  return items;
});

// Фильтрация по поисковому запросу
const filteredItems = computed(() => {
  if (!searchQuery.value.trim()) {
    return allItems.value;
  }
  const q = searchQuery.value.toLowerCase().trim();
  return allItems.value.filter(item => {
    const slug = (item.slug || '').toLowerCase();
    const nominative = (item.nominative || '').toLowerCase();
    const dative = (item.dative || '').toLowerCase();
    const genitive = (item.genitive || '').toLowerCase();
    return slug.includes(q) || nominative.includes(q) || dative.includes(q) || genitive.includes(q);
  });
});

const totalPagesTargets = computed(() => Math.max(1, Math.ceil(filteredItems.value.length / itemsPerPageTargets.value)));
const displayedItems = computed(() => {
  const end = currentPageTargets.value * itemsPerPageTargets.value;
  return filteredItems.value.slice(0, end);
});
const hasMoreItems = computed(() => {
  return displayedItems.value.length < filteredItems.value.length;
});

function getCurrentType(item) {
  if (!isHybrid.value) {
    return item.source === 'folder' ? 'folder' : 'subdomain';
  }
  // Проверяем, есть ли элемент с таким же slug в обеих коллекциях
  const folderExists = folders.value.some(f => f.slug === item.slug);
  const subdomainExists = subdomains.value.some(s => s.slug === item.slug);
  
  if (folderExists && subdomainExists) return 'both';
  if (item.source === 'folder') return 'folder';
  return 'subdomain';
}

async function onTypeChange(item, newType) {
  await onUpdateType(item, newType);
}

async function loadMode() {
  try {
    const { mode: m } = await api.getMode();
    if (m) mode.value = m;
  } catch (e) {
    console.error('Failed to load mode:', e);
  }
}

async function load() {
  loading.value = true;
  error.value = '';
  try {
    await loadMode();
    [folders.value, subdomains.value] = await Promise.all([
      api.getFolders(),
      api.getSubdomains(),
    ]);
    // Добавим дефолтные значения для падежей, если их нет
    folders.value.forEach(f => {
      if (!f.nominative) f.nominative = f.name || '';
      if (!f.dative) f.dative = '';
      if (!f.genitive) f.genitive = '';
    });
    subdomains.value.forEach(s => {
      if (!s.nominative) s.nominative = s.name || '';
      if (!s.dative) s.dative = '';
      if (!s.genitive) s.genitive = '';
    });
  } catch (e) {
    error.value = String(e.message || e);
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  load();
  // Слушаем обновление режима
  window.addEventListener('mr-ml-mode-updated', handleModeUpdate);
  // ESC закрывает подтверждение удаления
  const onKey = (e) => {
    if (e.key === 'Escape' && deleteConfirmId.value) {
      deleteConfirmId.value = null;
    }
  };
  window.addEventListener('keydown', onKey);
  (window).__mr_ml_targets_onKey = onKey;
  // Клик вне тултипа закрывает его
  const onDocClick = (e) => {
    if (!deleteConfirmId.value) return;
    const target = e.target;
    if (target && target.closest && target.closest('[data-mr-ml-delete-wrap]')) return;
    deleteConfirmId.value = null;
  };
  window.addEventListener('click', onDocClick, true);
  (window).__mr_ml_targets_onDocClick = onDocClick;
});

onUnmounted(() => {
  window.removeEventListener('mr-ml-mode-updated', handleModeUpdate);
  const onKey = (window).__mr_ml_targets_onKey;
  if (onKey) window.removeEventListener('keydown', onKey);
  const onDocClick = (window).__mr_ml_targets_onDocClick;
  if (onDocClick) window.removeEventListener('click', onDocClick, true);
});

function handleModeUpdate(event) {
  if (event.detail && event.detail.mode) {
    mode.value = event.detail.mode;
    load(); // Перезагружаем данные при изменении режима
  } else {
    loadMode();
    load(); // Перезагружаем данные
  }
}

async function onCreate() {
  if (!draft.slug) return;
  
  const payload = {
    slug: draft.slug,
    name: draft.nominative || draft.slug,
    nominative: draft.nominative,
    dative: draft.dative,
    genitive: draft.genitive,
  };
  
  try {
    if (isHybrid.value && draft.type !== 'both') {
      if (draft.type === 'folder') {
        const created = await api.createFolder(payload);
        folders.value.push(created);
      } else {
        const created = await api.createSubdomain(payload);
        subdomains.value.push(created);
      }
    } else {
      // Если "Везде" или не гибрид - создаем в обоих
      if (isHybrid.value || mode.value === 'folder') {
        const createdF = await api.createFolder(payload);
        folders.value.push(createdF);
      }
      if (isHybrid.value || mode.value === 'subdomain') {
        const createdS = await api.createSubdomain(payload);
        subdomains.value.push(createdS);
      }
    }
    
    draft.type = 'both';
    draft.slug = '';
    draft.nominative = '';
    draft.dative = '';
    draft.genitive = '';
    showAddModal.value = false;
    showMessage('Элемент успешно создан', 'success');
  } catch (e) {
    showMessage('Ошибка создания: ' + String(e.message || e), 'error');
  }
}

function showMessage(msg, type = 'success') {
  window.dispatchEvent(new CustomEvent('mr-ml-notify', { detail: { message: msg, type } }));
}

function cancelAdd() {
  draft.type = 'both';
  draft.slug = '';
  draft.nominative = '';
  draft.dative = '';
  draft.genitive = '';
  showAddModal.value = false;
}

async function onUpdateType(item, newType) {
  const payload = {
    slug: item.slug,
    name: item.nominative || item.slug,
    nominative: item.nominative,
    dative: item.dative,
    genitive: item.genitive,
  };
  
  const currentType = getCurrentType(item);
  
  // Если тип не изменился - ничего не делаем
  if (currentType === newType) return;
  
  try {
    if (newType === 'both') {
      // Должен быть везде - создаем в обоих, если нет
      const folderItem = folders.value.find(f => f.slug === item.slug);
      const subdomainItem = subdomains.value.find(s => s.slug === item.slug);
      
      // Обновляем или создаем в папках
      if (folderItem) {
        await api.updateFolder(folderItem.id, payload);
      } else {
        const created = await api.createFolder(payload);
        folders.value.push(created);
      }
      
      // Обновляем или создаем в поддоменах
      if (subdomainItem) {
        await api.updateSubdomain(subdomainItem.id, payload);
      } else {
        const created = await api.createSubdomain(payload);
        subdomains.value.push(created);
      }
    } else if (newType === 'folder') {
      // Только в папках
      const folderItem = folders.value.find(f => f.slug === item.slug || f.id === item.id);
      const subdomainItem = subdomains.value.find(s => s.slug === item.slug);
      
      // Обновляем или создаем в папках
      if (folderItem) {
        await api.updateFolder(folderItem.id, payload);
      } else {
        const created = await api.createFolder(payload);
        folders.value.push(created);
      }
      
      // Удаляем из поддоменов, если есть
      if (subdomainItem) {
        await api.deleteSubdomain(subdomainItem.id);
      }
    } else if (newType === 'subdomain') {
      // Только в поддоменах
      const folderItem = folders.value.find(f => f.slug === item.slug);
      const subdomainItem = subdomains.value.find(s => s.slug === item.slug || s.id === item.id);
      
      // Обновляем или создаем в поддоменах
      if (subdomainItem) {
        await api.updateSubdomain(subdomainItem.id, payload);
      } else {
        const created = await api.createSubdomain(payload);
        subdomains.value.push(created);
      }
      
      // Удаляем из папок, если есть
      if (folderItem) {
        await api.deleteFolder(folderItem.id);
      }
    }
    
    // Перезагружаем данные
    await load();
    showMessage('Тип элемента успешно изменен', 'success');
  } catch (e) {
    showMessage('Ошибка изменения типа: ' + String(e.message || e), 'error');
    await load(); // Перезагрузить при ошибке
  }
}

async function onUpdate(item) {
  const payload = {
    slug: item.slug,
    name: item.nominative || item.slug,
    nominative: item.nominative,
    dative: item.dative,
    genitive: item.genitive,
  };
  
  try {
    // Если элемент должен быть везде - обновляем в обоих местах
    if (isHybrid.value && getCurrentType(item) === 'both') {
      const folderItem = folders.value.find(f => f.slug === item.slug);
      const subdomainItem = subdomains.value.find(s => s.slug === item.slug);
      
      if (folderItem) await api.updateFolder(folderItem.id, payload);
      if (subdomainItem) await api.updateSubdomain(subdomainItem.id, payload);
    } else {
      // Обычное обновление
      if (item.source === 'folder') {
        await api.updateFolder(item.id, payload);
      } else {
        await api.updateSubdomain(item.id, payload);
      }
    }
    
    // Перезагружаем данные
    await load();
    showMessage('Элемент успешно обновлен', 'success');
  } catch (e) {
    showMessage('Ошибка обновления: ' + String(e.message || e), 'error');
    await load(); // Перезагрузить при ошибке
  }
}

function startEdit(item) {
  isEditingId.value = item.id;
  // Сохраняем исходные значения
  const folderItem = folders.value.find(f => f.slug === item.slug || f.id === item.id);
  const subdomainItem = subdomains.value.find(s => s.slug === item.slug || s.id === item.id);
  originalValues.value[item.id] = {
    slug: item.slug,
    nominative: item.nominative || '',
    dative: item.dative || '',
    genitive: item.genitive || '',
    source: item.source,
    folderId: folderItem?.id,
    subdomainId: subdomainItem?.id,
  };
}

async function commitEdit(item) {
  if (isEditingId.value !== item.id) return;
  
  const original = originalValues.value[item.id];
  if (!original) {
    isEditingId.value = null;
    return;
  }
  
  // Проверяем, были ли изменения
  const hasChanges = 
    item.slug !== original.slug ||
    (item.nominative || '') !== original.nominative ||
    (item.dative || '') !== original.dative ||
    (item.genitive || '') !== original.genitive;
  
  if (!hasChanges) {
    isEditingId.value = null;
    delete originalValues.value[item.id];
    return; // Ничего не изменилось
  }
  
  await onUpdate(item);
  isEditingId.value = null;
  delete originalValues.value[item.id];
}

function cancelDelete() {
  deleteConfirmId.value = null;
}

async function confirmDelete() {
  const item = deleteConfirmItem.value;
  if (!item) return;
  
  const itemId = item.id;
  deleteConfirmId.value = null;
  
  try {
    if (item.source === 'folder') {
      await api.deleteFolder(item.id);
      folders.value = folders.value.filter(r => r.id !== item.id);
    } else {
      await api.deleteSubdomain(item.id);
      subdomains.value = subdomains.value.filter(r => r.id !== item.id);
    }
    showMessage('Элемент успешно удален', 'success');
  } catch (e) {
    showMessage('Ошибка удаления: ' + String(e.message || e), 'error');
  }
}

async function onDelete(item) {
  deleteConfirmId.value = item.id;
}

function showMore() {
  if (hasMoreItems.value) {
    currentPageTargets.value++;
  }
}

// Используем функцию подсветки из утилит

// Сбрасываем страницу при изменении поиска или количества на странице
watch([searchQuery, itemsPerPageTargets], () => {
  currentPageTargets.value = 1;
});
</script>
