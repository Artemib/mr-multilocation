<template>
  <div class="relative">
    <div 
      v-if="!isEditing"
      @dblclick="startEdit"
      class="border rounded px-2 py-1 w-36 bg-slate-100 cursor-pointer min-h-[2rem] flex items-center"
      v-html="highlightedValue"
    ></div>
    <Input 
      v-else
      :model-value="modelValue"
      :size="'sm'"
      @update:model-value="$emit('update:modelValue', $event)"
      @keydown.enter.prevent="commitEdit"
      @blur="commitEdit"
      class="w-36"
      autofocus
    />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import Input from './Input.vue';

const props = defineProps({
  modelValue: {
    type: String,
    required: true,
  },
  highlightQuery: {
    type: String,
    default: '',
  },
  highlightFunction: {
    type: Function,
    default: null,
  },
});

const emit = defineEmits(['update:modelValue', 'save', 'edit', 'cancel']);

const isEditing = ref(false);
const originalValue = ref('');

const highlightedValue = computed(() => {
  if (props.highlightFunction && props.highlightQuery) {
    return props.highlightFunction(props.modelValue, props.highlightQuery);
  }
  return props.modelValue || '';
});

function startEdit() {
  isEditing.value = true;
  originalValue.value = props.modelValue;
  emit('edit');
}

function commitEdit() {
  if (props.modelValue !== originalValue.value) {
    emit('save', props.modelValue, originalValue.value);
  } else {
    emit('cancel');
  }
  isEditing.value = false;
}

// Сброс режима редактирования при изменении значения извне
watch(() => props.modelValue, () => {
  if (!isEditing.value) {
    originalValue.value = props.modelValue;
  }
});
</script>

