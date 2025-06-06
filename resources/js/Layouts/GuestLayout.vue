<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";

import { ref, watch, onMounted } from "vue";
import { Head, Link } from "@inertiajs/vue3";

// Управление состоянием мобильного меню
const isMenuOpen = ref(false);
</script>

<template>
  <section class="min-h-screen flex flex-col bg-gray-100 text-gray-800">
    <!-- Навигационная панель -->
    <header class="bg-white shadow-md fixed top-0 left-0 z-50 w-full">
      <div
        class="container mx-auto px-6 py-4 flex items-center justify-between"
      >
        <!-- Логотип -->
        <Link href="/" class="flex items-center gap-2">
          <!-- <img src="/telegram-logo-new.png" alt="Логотип" class="h-10" /> -->
          <span class="font-semibold text-lg text-blue-700"
            >Раскрутка Telegram</span
          >
        </Link>

        <!-- Горизонтальное меню -->
        <nav class="hidden lg:flex items-center gap-6">
          <Link href="/#services" class="menu-item">Услуги</Link>
          <Link href="/#features" class="menu-item">Возможности</Link>
          <Link href="/#order" class="menu-item">Заявка</Link>
          <Link href="/#contacts" class="menu-item">Контакты</Link>
        </nav>

        <!-- Кнопки авторизации -->
        <div class="hidden lg:flex gap-4">
          <Link href="/login" class="btn btn-primary">Войти</Link>
          <Link href="/register" class="btn btn-secondary">Регистрация</Link>
        </div>

        <!-- Мобильное меню -->
        <button @click="isMenuOpen = !isMenuOpen" class="lg:hidden">
          <svg
            v-if="!isMenuOpen"
            class="w-6 h-6 text-gray-700"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6h16M4 12h16m-7 6h7"
            />
          </svg>
          <svg
            v-else
            class="w-6 h-6 text-gray-700"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <!-- Выпадающее мобильное меню -->
      <div v-if="isMenuOpen" class="lg:hidden bg-white shadow-lg">
        <nav class="flex flex-col items-start gap-4 p-4 border-t">
          <Link href="/#services" class="mobile-menu-item">Услуги</Link>
          <Link href="/#features" class="mobile-menu-item">Возможности</Link>
          <Link href="/#order" class="mobile-menu-item">Заявка</Link>
          <Link href="/#contacts" class="mobile-menu-item">Контакты</Link>
          <Link href="/login" class="btn btn-primary w-full">Войти</Link>
          <Link href="/register" class="btn btn-secondary w-full"
            >Регистрация</Link
          >
        </nav>
      </div>
    </header>
    <!-- Контейнер с центровкой -->
    <div class="flex flex-grow justify-center items-center bg-[white]">
      <div
        class="w-full sm:max-w-md mt-6 px-6 py-4 bg-[#f4f4f4] shadow-md overflow-hidden sm:rounded-lg"
      >
        <slot />
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Общие стили текста и элементов меню */
.menu-item {
  font-size: 1rem;
  font-weight: 500;
  color: #374151;
  transition: color 0.3s;
  cursor: pointer;
}
.menu-item:hover {
  color: #2563eb;
}

.mobile-menu-item {
  font-size: 1.125rem;
  font-weight: 600;
  color: #374151;
  width: 100%;
  text-align: left;
  padding: 0.5rem 1rem;
  transition: color 0.3s, background 0.3s;
}
.mobile-menu-item:hover {
  color: #ffffff;
  background-color: #2563eb;
}

/* Стили кнопок */
.btn {
  font-weight: 500;
  text-align: center;
  padding: 0.5rem 1.5rem;
  border-radius: 0.375rem;
  transition: background-color 0.3s;
}
.btn-primary {
  background-color: #2563eb;
  color: #ffffff;
}
.btn-primary:hover {
  background-color: #1d4ed8;
}
.btn-secondary {
  background-color: #f59e0b;
  color: #ffffff;
}
.btn-secondary:hover {
  background-color: #d97706;
}
</style>
