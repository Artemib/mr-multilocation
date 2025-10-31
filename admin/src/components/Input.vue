<template>
  <input 
    :type="type"
    :value="modelValue"
    :placeholder="placeholder"
    :readonly="readonly"
    :disabled="disabled"
    :class="inputClasses"
    @input="$emit('update:modelValue', $event.target.value); $emit('input', $event)"
    @keydown.enter="$emit('enter', $event)"
    @blur="$emit('blur', $event)"
    @focus="$emit('focus', $event)"
    @dblclick="$emit('dblclick', $event)"
  />
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  type: {
    type: String,
    default: 'text',
  },
  placeholder: {
    type: String,
    default: '',
  },
  readonly: {
    type: Boolean,
    default: false,
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

defineEmits(['update:modelValue', 'enter', 'blur', 'focus', 'input', 'dblclick']);

const inputClasses = computed(() => {
  const base = 'border rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed';
  
  const sizes = {
    sm: 'px-2 py-1 text-xs',
    md: 'px-2 py-1 text-sm',
    lg: 'px-3 py-2 text-base',
  };
  
  const state = props.readonly 
    ? 'bg-slate-100 cursor-pointer' 
    : props.disabled 
      ? 'bg-slate-100' 
      : 'bg-white';
  
  return `${base} ${sizes[props.size]} ${state}`;
});
</script>

