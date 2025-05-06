<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 flex items-center justify-center bg-gray-500 bg-opacity-50"
  >
    <div
      class="bg-white rounded-lg shadow-lg p-6 w-[500px] max-h-[90vh] overflow-y-auto relative"
    >
      <!-- Кнопка для закрытия модального окна -->
      <!-- Стильный крестик для закрытия модального окна -->
      <!-- Увеличенный стильный крестик для закрытия модального окна -->
      <button
        @click="close"
        class="absolute top-3 right-3 text-gray-500 hover:text-gray-900 transition duration-200 ease-in-out text-3xl font-extrabold"
        aria-label="Close modal"
      >
        &times;
      </button>

      <h3 class="text-lg font-bold mb-4 text-center" v-if="title">
        {{ title }}
      </h3>

      <slot></slot>
    </div>
  </div>
</template>

  <script setup>
import { defineProps, defineEmits } from "vue";

const props = defineProps({
  title: {
    type: String,
    default: "Модальное окно",
  },
  isOpen: {
    type: Boolean,
    required: true,
  },
});

const emit = defineEmits(["close"]); // Оповещение о закрытии

function close() {
  emit("close"); // Оповещаем родительский компонент о закрытии модального окна
}
</script>

  <style>
/* Добавьте дополнительные стили, если нужно */
</style>
