<template>
  <div class="mb-4">
    <label v-if="label" class="text-sm text-slate-600 mb-1 block">{{ label }}</label>
    <Input 
      :model-value="modelValue"
      :placeholder="placeholder"
      type="text"
      :size="size"
      @update:model-value="$emit('update:modelValue', $event)"
    />
  </div>
</template>

<script setup>
import Input from './Input.vue';

defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  label: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Введите текст для поиска...',
  },
  size: {
    type: String,
    default: 'md',
  },
});

defineEmits(['update:modelValue']);
</script>

