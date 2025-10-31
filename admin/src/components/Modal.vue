<template>
  <div 
    v-if="modelValue"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    @click.self="$emit('update:modelValue', false)"
  >
    <div :class="modalClasses">
      <div class="flex items-center justify-between mb-4">
        <h3 v-if="title" class="text-lg font-semibold">{{ title }}</h3>
        <div class="flex items-center gap-2">
          <slot name="header-actions"></slot>
          <button 
            class="text-slate-500 hover:text-slate-700 text-xl leading-none"
            @click="$emit('update:modelValue', false)"
          >
            ✕
          </button>
        </div>
      </div>
      <slot></slot>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  title: {
    type: String,
    default: '',
  },
  size: {
    type: String,
    default: 'md', // sm, md, lg, xl
  },
  maxHeight: {
    type: String,
    default: '90vh',
  },
});

const emit = defineEmits(['update:modelValue']);

const modalClasses = computed(() => {
  const base = 'bg-white rounded-lg p-6 shadow-lg max-h-[90vh] overflow-auto';
  
  const sizes = {
    sm: 'max-w-md w-full',
    md: 'max-w-2xl w-full',
    lg: 'max-w-3xl w-full',
    xl: 'max-w-4xl w-full',
  };
  
  return `${base} ${sizes[props.size]}`;
});

// Закрытие по ESC

let escHandler = null;
let scrollbarWidth = 0;
let modalCount = 0;

// Блокировка скролла body при открытии модального окна
function lockBodyScroll() {
  modalCount++;
  
  if (modalCount === 1) {
    // Сохраняем ширину скроллбара только при первом открытии
    scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
    
    // Блокируем скролл и добавляем padding справа равный ширине скроллбара
    document.body.style.overflow = 'hidden';
    if (scrollbarWidth > 0) {
      document.body.style.paddingRight = `${scrollbarWidth}px`;
    }
  }
}

// Разблокировка скролла body при закрытии модального окна
function unlockBodyScroll() {
  modalCount = Math.max(0, modalCount - 1);
  
  if (modalCount === 0) {
    // Разблокируем скролл только если все модальные окна закрыты
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
  }
}

watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    // Блокируем скролл страницы
    lockBodyScroll();
    
    // Добавляем обработчик ESC
    escHandler = (e) => {
      if (e.key === 'Escape') {
        emit('update:modelValue', false);
      }
    };
    window.addEventListener('keydown', escHandler);
  } else {
    // Разблокируем скролл страницы
    unlockBodyScroll();
    
    // Удаляем обработчик ESC
    if (escHandler) {
      window.removeEventListener('keydown', escHandler);
      escHandler = null;
    }
  }
}, { immediate: true });

onUnmounted(() => {
  // Разблокируем скролл при размонтировании
  unlockBodyScroll();
  
  // Удаляем обработчик ESC
  if (escHandler) {
    window.removeEventListener('keydown', escHandler);
  }
});
</script>

