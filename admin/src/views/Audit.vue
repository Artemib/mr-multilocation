<template>
  <div>
    <h3 class="text-lg font-medium mb-2">Аудит шорткодов</h3>
    <p class="text-slate-600 mb-4">REST: `mr-ml/v1/audit/*`.</p>
    <div class="flex items-center gap-2">
      <button class="bg-slate-700 text-white px-3 py-1 rounded" @click="reindex" :disabled="loading">Пересканировать</button>
      <span v-if="status" class="text-slate-600">{{ status }}</span>
    </div>
    <ul class="mt-4 list-disc pl-5 text-slate-700">
      <li v-for="row in results" :key="row.id">{{ row.postTitle }} — {{ row.shortcodes.join(', ') }}</li>
    </ul>
  </div>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue';
const api = inject('api');
const status = ref('');
const loading = ref(false);
const results = ref([]);

async function load() {
  results.value = await api.getAuditShortcodes();
}

onMounted(load);

async function reindex() {
  loading.value = true; status.value = 'Запущено...';
  try {
    const res = await api.runAuditReindex();
    status.value = `Готово: ${res.indexed}`;
    await load();
  } finally {
    loading.value = false;
  }
}
</script>


