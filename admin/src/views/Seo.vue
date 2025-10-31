<template>
  <div>
    <h3 class="text-lg font-medium mb-2">SEO</h3>
    
    <!-- Определение SEO плагинов -->
    <div v-if="detectedSeoPlugins.length > 0" class="mb-6 p-4 border rounded bg-slate-50">
      <h4 class="font-medium mb-2">Найденные SEO плагины:</h4>
      <ul class="list-disc list-inside text-sm text-slate-600 mb-4">
        <li v-for="plugin in detectedSeoPlugins" :key="plugin.type">{{ plugin.name }}</li>
      </ul>
      
      <div class="mb-4">
        <label class="block text-sm font-medium text-slate-700 mb-2">Выберите SEO-плагин для синхронизации:</label>
        <Select v-model="activeSeoPlugin" class="w-full">
          <option value="">Не синхронизировать (использовать только встроенные поля)</option>
          <option v-for="plugin in detectedSeoPlugins" :key="plugin.type" :value="plugin.type">
            {{ plugin.name }}
          </option>
        </Select>
        <p class="text-xs text-slate-500 mt-1">
          При выборе SEO-плагина метаданные будут синхронизированы: изменения в нашем плагине будут отражаться в SEO-плагине и наоборот.
        </p>
      </div>
    </div>
    
    <div v-else class="mb-6 p-4 border rounded bg-blue-50">
      <p class="text-sm text-blue-800">
        SEO-плагины не обнаружены. Будут использоваться только встроенные метаполя плагина.
      </p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <h4 class="font-medium mb-2">Флаги</h4>
        <label class="flex items-center gap-2 mb-2">
          <input type="checkbox" v-model="enableCanonical"> Canonical
        </label>
        <label class="flex items-center gap-2">
          <input type="checkbox" v-model="enableHreflang"> Hreflang
        </label>
      </div>
      <div>
        <h4 class="font-medium mb-2">Robots шаблоны</h4>
        <textarea v-model="robotsGoogle" class="w-full h-32 border rounded p-2" placeholder="robots.txt (Google)"></textarea>
        <textarea v-model="robotsYandex" class="w-full h-32 border rounded p-2 mt-2" placeholder="robots.txt (Yandex)"></textarea>
      </div>
    </div>
    <div class="mt-4">
      <Button variant="primary" @click="save">Сохранить</Button>
    </div>
  </div>
</template>

<script setup>
import { inject, ref, onMounted } from 'vue';
import { Button, Select } from '../components';
const api = inject('api');

const enableCanonical = ref(true);
const enableHreflang = ref(true);
const robotsGoogleDisallowFolders = ref(false);
const robotsYandexDisallowSubdomains = ref(false);
const robotsGoogle = ref('');
const robotsYandex = ref('');
const detectedSeoPlugins = ref([]);
const activeSeoPlugin = ref('');

async function load() {
  const data = await api.getSeo();
  enableCanonical.value = !!data.enableCanonical;
  enableHreflang.value = !!data.enableHreflang;
  robotsGoogleDisallowFolders.value = !!data.robotsGoogleDisallowFolders;
  robotsYandexDisallowSubdomains.value = !!data.robotsYandexDisallowSubdomains;
  robotsGoogle.value = data.robotsTplGoogle || '';
  robotsYandex.value = data.robotsTplYandex || '';
  detectedSeoPlugins.value = data.detectedSeoPlugins || [];
  activeSeoPlugin.value = data.activeSeoPlugin || '';
}

onMounted(load);

function showMessage(msg, type = 'success') {
  window.dispatchEvent(new CustomEvent('mr-ml-notify', { detail: { message: msg, type } }));
}

async function save() {
  try {
    await api.setSeo({
      enableCanonical: enableCanonical.value,
      enableHreflang: enableHreflang.value,
      robotsGoogleDisallowFolders: robotsGoogleDisallowFolders.value,
      robotsYandexDisallowSubdomains: robotsYandexDisallowSubdomains.value,
      robotsTplGoogle: robotsGoogle.value,
      robotsTplYandex: robotsYandex.value,
      activeSeoPlugin: activeSeoPlugin.value,
    });
    showMessage('SEO настройки успешно сохранены', 'success');
  } catch (e) {
    showMessage('Ошибка сохранения: ' + String(e.message || e), 'error');
  }
}
</script>


