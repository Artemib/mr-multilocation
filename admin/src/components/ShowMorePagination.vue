<template>
  <div class="flex items-center justify-between mt-3 text-sm">
    <div class="text-sm text-slate-600">
      <slot name="info">
        Показано: {{ displayed }} из {{ total }}
      </slot>
    </div>
    
    <div class="flex flex-col items-center gap-2">
      <button 
        v-if="hasMore"
        class="px-4 py-2 border rounded bg-blue-600 text-white disabled:opacity-50 hover:bg-blue-700" 
        :disabled="!hasMore" 
        @click="$emit('showMore')"
      >
        Показать ещё
      </button>
    </div>
    
    <div class="flex items-center gap-2">
      <span>Показывать по:</span>
      <Select 
        :model-value="itemsPerPage"
        :size="'sm'"
        @update:model-value="$emit('update:itemsPerPage', Number($event))"
      >
        <option :value="10">10</option>
        <option :value="25">25</option>
        <option :value="50">50</option>
      </Select>
    </div>
  </div>
</template>

<script setup>
import Select from './Select.vue';

defineProps({
  displayed: {
    type: Number,
    required: true,
  },
  total: {
    type: Number,
    required: true,
  },
  hasMore: {
    type: Boolean,
    required: true,
  },
  itemsPerPage: {
    type: Number,
    default: 10,
  },
});

defineEmits(['showMore', 'update:itemsPerPage']);
</script>

