<template>
  <div>
    <h3 class="text-lg font-medium mb-2">SEO</h3>
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
import { Button } from '../components';
const api = inject('api');

const enableCanonical = ref(true);
const enableHreflang = ref(true);
const robotsGoogleDisallowFolders = ref(false);
const robotsYandexDisallowSubdomains = ref(false);
const robotsGoogle = ref('');
const robotsYandex = ref('');

async function load() {
  const data = await api.getSeo();
  enableCanonical.value = !!data.enableCanonical;
  enableHreflang.value = !!data.enableHreflang;
  robotsGoogleDisallowFolders.value = !!data.robotsGoogleDisallowFolders;
  robotsYandexDisallowSubdomains.value = !!data.robotsYandexDisallowSubdomains;
  robotsGoogle.value = data.robotsTplGoogle || '';
  robotsYandex.value = data.robotsTplYandex || '';
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
    });
    showMessage('SEO настройки успешно сохранены', 'success');
  } catch (e) {
    showMessage('Ошибка сохранения: ' + String(e.message || e), 'error');
  }
}
</script>


