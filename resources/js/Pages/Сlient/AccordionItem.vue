<script setup>
import { ref, watch, defineProps, defineEmits } from "vue";

const props = defineProps({
  title: String,
  modelValue: Boolean, // v-model
});
const emit = defineEmits(["update:modelValue"]);

const isOpen = ref(props.modelValue ?? false);

// Если родитель меняет modelValue — обновляем локальное isOpen
watch(
  () => props.modelValue,
  (val) => {
    isOpen.value = val;
  }
);

// Обработка клика
const toggle = () => {
  isOpen.value = !isOpen.value;
  emit("update:modelValue", isOpen.value); // сообщаем родителю
};
</script>

<template>
  <div class="mb-4">
    <h2
      @click="toggle"
      class="cursor-pointer flex items-center justify-between text-[1.2rem] md:text-[1.2rem] font-bold text-white bg-[#45A0F2] px-6 py-4 shadow-xl rounded-[5px]"
    >
      <span>{{ title }}</span>
      <svg
        :class="[
          'w-5 h-5 transition-transform duration-300',
          isOpen ? 'rotate-180' : 'rotate-0',
        ]"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        viewBox="0 0 24 24"
        xmlns="http://www.w3.org/2000/svg"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M19 9l-7 7-7-7"
        />
      </svg>
    </h2>

    <div v-show="isOpen" class="transition-all duration-300 overflow-hidden">
      <slot />
    </div>
  </div>
</template>
