<template>
  <div 
    v-if="show"
    :class="alertClasses"
    class="p-3 rounded mb-2 flex items-center justify-between"
  >
    <span><slot></slot></span>
    <button 
      v-if="dismissible"
      @click="$emit('dismiss')"
      class="ml-4 text-lg leading-none"
    >
      ×
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  type: {
    type: String,
    default: 'info', // info, success, warning, error
  },
  show: {
    type: Boolean,
    default: true,
  },
  dismissible: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['dismiss']);

const alertClasses = computed(() => {
  const types = {
    info: 'bg-blue-100 text-blue-800 border border-blue-300',
    success: 'bg-green-100 text-green-800 border border-green-300',
    warning: 'bg-yellow-100 text-yellow-800 border border-yellow-300',
    error: 'bg-red-100 text-red-800 border border-red-300',
  };
  
  return types[props.type] || types.info;
});
</script>

