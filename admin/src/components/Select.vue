<template>
  <select 
    :value="modelValue"
    :disabled="disabled"
    :class="selectClasses"
    @change="$emit('update:modelValue', $event.target.value)"
  >
    <slot></slot>
  </select>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  size: {
    type: String,
    default: 'md', // sm, md, lg
  },
});

defineEmits(['update:modelValue']);

const selectClasses = computed(() => {
  const base = 'border rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed bg-white';
  
  const sizes = {
    sm: 'px-2 py-1 text-xs',
    md: 'px-2 py-1 text-sm',
    lg: 'px-3 py-2 text-base',
  };
  
  return `${base} ${sizes[props.size]}`;
});
</script>

