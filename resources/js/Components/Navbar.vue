<template>
  <header class="bg-white shadow-md fixed top-0 left-0 z-50 w-full">
    <div class="container mx-auto px-6 py-4 flex items-center justify-between">
      <!-- Логотип -->
      <Link href="/" class="flex items-center gap-2">
        <!-- <img src="/telegram-logo-new.png" alt="Логотип" class="h-10" /> -->
        <span class="font-semibold text-lg text-blue-700">
          Раскрутка Telegram
        </span>
      </Link>

      <!-- Горизонтальное меню -->
      <nav class="hidden lg:flex items-center gap-6">
        <button @click="handleScrollTo('#services')" class="menu-item">
          Услуги
        </button>
        <button @click="handleScrollTo('#features')" class="menu-item">
          Возможности
        </button>
        <button @click="handleScrollTo('#order')" class="menu-item">
          Заявка
        </button>
        <!-- <button @click="handleScrollTo('#steps')" class="menu-item">
          Как начать
        </button>
        <button @click="handleScrollTo('#pricing')" class="menu-item">
          Тарифы
        </button> -->
        <button @click="handleScrollTo('#contacts')" class="menu-item">
          Контакты
        </button>
      </nav>

      <!-- Кнопки авторизации -->
      <div class="hidden lg:flex gap-4">
        <Link href="/login" class="btn btn-primary">Войти</Link>
        <Link href="/register" class="btn btn-secondary">Регистрация</Link>
      </div>

      <!-- Кнопка бургера -->
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

    <!-- Мобильное меню -->
    <div v-if="isMenuOpen" class="lg:hidden bg-white shadow-lg">
      <nav class="flex flex-col items-start gap-4 p-4 border-t">
        <button @click="scrollAndClose('#services')" class="mobile-menu-item">
          Услуги
        </button>
        <button @click="scrollAndClose('#features')" class="mobile-menu-item">
          Возможности
        </button>
        <!-- <button @click="scrollAndClose('#steps')" class="mobile-menu-item">
          Как начать
        </button>
        <button @click="scrollAndClose('#pricing')" class="mobile-menu-item">
          Тарифы
        </button> -->
        <button @click="scrollAndClose('#order')" class="mobile-menu-item">
          Заявка
        </button>
        <button @click="scrollAndClose('#contacts')" class="mobile-menu-item">
          Контакты
        </button>
        <Link href="/login" class="btn btn-primary w-full">Войти</Link>
        <Link href="/register" class="btn btn-secondary w-full"
          >Регистрация</Link
        >
      </nav>
    </div>
  </header>
</template>

<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";

const isMenuOpen = ref(false);

const handleScrollTo = (selector) => {
  const el = document.querySelector(selector);
  if (el) {
    window.scrollTo({
      top: el.offsetTop - 80,
      behavior: "smooth",
    });
  }
};

const scrollAndClose = (selector) => {
  handleScrollTo(selector);
  isMenuOpen.value = false;
};
</script>

<style scoped>






.menu-item,
.mobile-menu-item {
  @apply text-gray-700 hover:text-blue-600 font-medium transition;
}
</style>
