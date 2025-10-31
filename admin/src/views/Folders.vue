<template>
  <div>
    <h3 class="text-lg font-medium mb-2">Папки</h3>
    <p class="text-slate-600 mb-4">CRUD будет через REST `mr-ml/v1/folders`.</p>
    <div class="flex gap-2">
      <input v-model="draft.slug" type="text" placeholder="slug" class="border rounded px-2 py-1" />
      <input v-model="draft.name" type="text" placeholder="Название" class="border rounded px-2 py-1" />
      <button class="bg-blue-600 text-white rounded px-3 py-1" @click="onCreate">Добавить</button>
    </div>
    <ul class="mt-4 space-y-2 text-slate-700">
      <li v-for="s in items" :key="s.id" class="flex items-center gap-2">
        <span>/</span>
        <input v-model="s.slug" class="border rounded px-2 py-1 w-36" />
        <input v-model="s.name" class="border rounded px-2 py-1 w-60" />
        <button class="bg-slate-600 text-white rounded px-2 py-1" @click="onUpdate(s)">Сохранить</button>
        <button class="bg-red-600 text-white rounded px-2 py-1" @click="onDelete(s)">Удалить</button>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { inject, reactive, ref, onMounted } from 'vue';
const api = inject('api');

const items = ref([]);
const loading = ref(false);
const draft = reactive({ slug: '', name: '' });
const error = ref('');

async function load() {
  loading.value = true; error.value = '';
  try {
    items.value = await api.getFolders();
  } catch (e) {
    error.value = String(e.message || e);
  } finally {
    loading.value = false;
  }
}

onMounted(load);

async function onCreate() {
  if (!draft.slug || !draft.name) return;
  try {
    const created = await api.createFolder({ slug: draft.slug, name: draft.name });
    items.value.push(created);
    draft.slug = '';
    draft.name = '';
  } catch (e) {
    alert(String(e.message || e));
  }
}

async function onUpdate(row) {
  try {
    await api.updateFolder(row.id, { slug: row.slug, name: row.name });
  } catch (e) {
    alert(String(e.message || e));
  }
}

async function onDelete(row) {
  if (!confirm('Удалить?')) return;
  try {
    await api.deleteFolder(row.id);
    items.value = items.value.filter(r => r.id !== row.id);
  } catch (e) {
    alert(String(e.message || e));
  }
}
</script>


