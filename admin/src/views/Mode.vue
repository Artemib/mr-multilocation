<template>
  <div>
    <h3 class="text-lg font-medium mb-2">Режим работы</h3>
    <p class="text-slate-600 mb-4">Выберите: поддомены, папки или гибрид.</p>
    
    <div class="grid grid-cols-3 gap-4 mb-4">
      <label class="cursor-pointer">
        <input type="radio" value="subdomain" v-model="mode" class="sr-only peer" />
        <div class="border-2 rounded-lg p-4 text-center transition-all peer-checked:border-blue-600 peer-checked:bg-blue-50 hover:border-blue-300">
          <div class="font-semibold text-lg mb-1">Поддомены</div>
          <div class="text-sm text-slate-600">msk.example.com</div>
        </div>
      </label>
      <label class="cursor-pointer">
        <input type="radio" value="folder" v-model="mode" class="sr-only peer" />
        <div class="border-2 rounded-lg p-4 text-center transition-all peer-checked:border-blue-600 peer-checked:bg-blue-50 hover:border-blue-300">
          <div class="font-semibold text-lg mb-1">Папки</div>
          <div class="text-sm text-slate-600">example.com/msk</div>
        </div>
      </label>
      <label class="cursor-pointer">
        <input type="radio" value="hybrid" v-model="mode" class="sr-only peer" />
        <div class="border-2 rounded-lg p-4 text-center transition-all peer-checked:border-blue-600 peer-checked:bg-blue-50 hover:border-blue-300">
          <div class="font-semibold text-lg mb-1">Гибрид</div>
          <div class="text-sm text-slate-600">Оба варианта</div>
        </div>
      </label>
    </div>
    
    <div v-if="mode !== 'subdomain'" class="mb-4 p-4 border rounded bg-slate-50">
      <label class="flex items-start gap-3 cursor-pointer">
        <input type="checkbox" v-model="mainNoPrefix" class="rounded mt-1" /> 
        <div>
          <div class="text-slate-700 font-medium mb-1">Основной сайт без префикса (для папок)</div>
          <div class="text-sm text-slate-600">
            Если включено, основной домен (например, example.com) будет работать без префикса папки. 
            Страницы будут доступны как <code class="bg-white px-1 rounded">example.com/page</code> вместо <code class="bg-white px-1 rounded">example.com/msk/page</code>.
            Это полезно, если основной сайт не привязан к конкретному региону.
          </div>
        </div>
      </label>
    </div>
    
    <div class="mb-4 text-sm text-slate-600">
      Текущий режим: <strong>{{ modeLabels[mode] || mode }}</strong>
    </div>
    
    <div>
      <Button variant="primary" @click="save">
        Сохранить режим
      </Button>
    </div>
  </div>
</template>

<script setup>
import { inject, ref, onMounted } from 'vue';
import { Button } from '../components';

const api = inject('api');
const mode = ref('hybrid');
const loading = ref(false);
const mainNoPrefix = ref(false);

const modeLabels = {
  subdomain: 'Поддомены',
  folder: 'Папки',
  hybrid: 'Гибрид',
};

async function load() {
  loading.value = true;
  try {
    const { mode: m, mainNoPrefix: flag } = await api.getMode();
    if (m) mode.value = m;
    mainNoPrefix.value = !!flag;
  } finally {
    loading.value = false;
  }
}

onMounted(load);

function showMessage(msg, type = 'success') {
  window.dispatchEvent(new CustomEvent('mr-ml-notify', { detail: { message: msg, type } }));
}

async function save() {
  try {
    await api.setMode(mode.value, mainNoPrefix.value);
    // Обновляем режим через событие для обновления App.vue
    window.dispatchEvent(new CustomEvent('mr-ml-mode-updated', { detail: { mode: mode.value } }));
    showMessage('Режим работы успешно сохранен', 'success');
  } catch (e) {
    showMessage('Ошибка сохранения: ' + String(e.message || e), 'error');
  }
}
</script>


