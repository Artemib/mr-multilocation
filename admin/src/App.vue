<template>
  <div class="mr-ml-admin font-sans">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-2xl font-semibold">MR Multilocation</h2>
        <p class="text-sm text-slate-500">Версия {{ boot.pluginVersion }}</p>
      </div>
      <div class="text-right text-sm text-slate-500">
        <span>REST: {{ boot.restUrl }}</span>
      </div>
    </div>

    <div class="border-b border-slate-200 mb-4">
      <nav class="-mb-px flex gap-2">
        <button v-for="t in tabs" :key="t.key"
                class="px-3 py-2 border-b-2"
                :class="activeTab === t.key ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-600 hover:text-slate-900'"
                @click="activeTab = t.key">
          {{ t.label }}
        </button>
      </nav>
    </div>
    

    <div class="bg-white border rounded-md p-4">
      <component :is="currentComponent" />
    </div>

    <!-- Global Notifications (offset ниже WP admin bar) -->
    <div class="fixed top-12 right-0 z-[9999] p-4 space-y-2 max-w-md">
      <div v-for="t in toasts" :key="t.id"
           :class="t.type === 'error' ? 'bg-red-100 text-red-800 border-red-300' : 'bg-green-100 text-green-800 border-green-300'"
           class="border rounded px-4 py-3 shadow-lg">
        {{ t.message }}
      </div>
    </div>
  </div>
  
</template>

<script setup>
import { inject, provide, ref, computed, watchEffect, onMounted, onUnmounted } from 'vue';
import Targets from './views/Targets.vue';
import Pages from './views/Pages.vue';
import Audit from './views/Audit.vue';
import Mode from './views/Mode.vue';
import Seo from './views/Seo.vue';
import { createApi } from './lib/api.js';

const boot = inject('boot');
const api = createApi(boot);
provide('api', api);
const mode = ref('hybrid');
const toasts = ref([]);

const tabs = computed(() => {
  let targetsLabel = 'Поддомены/Папки';
  if (mode.value === 'folder') targetsLabel = 'Папки';
  else if (mode.value === 'subdomain') targetsLabel = 'Поддомены';
  
  return [
    { key: 'targets', label: targetsLabel, component: Targets },
    { key: 'pages', label: 'Страницы', component: Pages },
    { key: 'audit', label: 'Аудит шорткодов', component: Audit },
    { key: 'mode', label: 'Режим работы', component: Mode },
    { key: 'seo', label: 'SEO', component: Seo },
  ];
});

async function loadMode() {
  try {
    const { mode: m } = await api.getMode();
    if (m) mode.value = m;
  } catch (e) {
    console.error('Failed to load mode:', e);
  }
}

loadMode();

const saved = typeof window !== 'undefined' ? window.localStorage.getItem('mr_ml_active_tab') : null;
const activeTab = ref(saved && tabs.value.some(t=>t.key===saved) ? saved : tabs.value[0].key);
const currentComponent = computed(() => tabs.value.find(t => t.key === activeTab.value)?.component || tabs.value[0].component);

// persist tab
watchEffect(() => {
  try { window.localStorage.setItem('mr_ml_active_tab', activeTab.value); } catch(e) {}
});

// Обновляем режим при переключении вкладок
watchEffect(() => {
  if (activeTab.value === 'targets' || activeTab.value === 'mode') {
    loadMode();
  }
});

// Слушаем событие обновления режима из Mode.vue
function handleModeUpdate(event) {
  if (event.detail && event.detail.mode) {
    mode.value = event.detail.mode;
  } else {
    loadMode();
  }
}

onMounted(() => {
  window.addEventListener('mr-ml-mode-updated', handleModeUpdate);
  window.addEventListener('mr-ml-notify', handleNotify);
});

onUnmounted(() => {
  window.removeEventListener('mr-ml-mode-updated', handleModeUpdate);
  window.removeEventListener('mr-ml-notify', handleNotify);
});

function handleNotify(event) {
  const { message, type } = event.detail || {};
  if (!message) return;
  const id = Date.now() + Math.random();
  toasts.value.push({ id, message, type: type || 'success' });
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id);
  }, type === 'error' ? 5000 : 3000);
}
</script>

<style scoped>
.mr-ml-admin {
  /* keep basic spacing compatible with WP */
}
</style>


