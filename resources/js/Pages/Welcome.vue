<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";

// Управление состоянием мобильного меню
const isMenuOpen = ref(false);

// Функция для плавного скроллинга к заданным секциям
const handleScrollTo = (id) => {
  const target = document.querySelector(id);
  if (target) {
    target.scrollIntoView({
      behavior: "smooth",
      block: "start",
    });
    isMenuOpen.value = false; // Закрываем меню после клика
  }
};
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
          <button @click="handleScrollTo('#services')" class="menu-item">
            Услуги
          </button>
          <button @click="handleScrollTo('#features')" class="menu-item">
            Возможности
          </button>
          <button @click="handleScrollTo('#steps')" class="menu-item">
            Как начать
          </button>
          <button @click="handleScrollTo('#pricing')" class="menu-item">
            Тарифы
          </button>
          <button @click="handleScrollTo('#contacts')" class="menu-item">
            Контакты
          </button>
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
          <button @click="handleScrollTo('#services')" class="mobile-menu-item">
            Услуги
          </button>
          <button @click="handleScrollTo('#features')" class="mobile-menu-item">
            Возможности
          </button>
          <button @click="handleScrollTo('#steps')" class="mobile-menu-item">
            Как начать
          </button>
          <button @click="handleScrollTo('#pricing')" class="mobile-menu-item">
            Тарифы
          </button>
          <button @click="handleScrollTo('#contacts')" class="mobile-menu-item">
            Контакты
          </button>
          <Link href="/login" class="btn btn-primary w-full">Войти</Link>
          <Link href="/register" class="btn btn-secondary w-full"
            >Регистрация</Link
          >
        </nav>
      </div>
    </header>

    <!-- Основной контент -->
    <main class="flex-grow pt-20 lg:pt-24">
      <!-- Раздел Вступление -->
      <section id="intro" class="text-center py-20 bg-white shadow">
        <div class="container mx-auto px-6">
          <!--  <span class="text-4xl font-bold text-blue-700 mb-12 block"
            >Добро пожаловь!</span
          > -->

          <h1 class="text-4xl font-bold text-blue-700">
            Раскрутите ваш Telegram-канал быстро и просто.
          </h1>
          <p class="mt-4 text-gray-600">
            Мы увеличим вашу аудиторию, повысим охваты и создадим успешное
            сообщество любого масштаба.
          </p>
          <!-- <img
            src="/images/telegram-intro-light.jpg"
            alt="Telegram Promotion"
            class="mt-6 mx-auto rounded-lg shadow-lg w-full md:w-3/4 lg:w-1/2"
          /> -->
        </div>
      </section>

      <!-- Раздел Услуги -->
      <section id="services" class="bg-gray-50 py-16">
        <div class="container mx-auto px-6 text-center">
          <h2 class="text-3xl font-bold text-blue-700">Наши Услуги</h2>
          <p class="mt-4 text-gray-600">
            Мы помогаем вам реализовать идеи и создать успешный Telegram-канал.
          </p>
          <div class="mt-8 grid grid-cols-1 gap-8 md:grid-cols-3">
            <div class="service-card">
              <img
                src="/images/setup-light.jpg"
                alt="Setup"
                class="rounded-lg mb-4 w-full"
              />
              <h3 class="text-xl font-bold text-gray-800">Создание канала</h3>
              <p class="mt-2 text-gray-600">
                Оформим ваш Telegram-канал профессионально.
              </p>
            </div>
            <div class="service-card">
              <img
                src="/images/subscribers-light.jpg"
                alt="Subscribers"
                class="rounded-lg mb-4 w-full"
              />
              <h3 class="text-xl font-bold text-gray-800">
                Привлечение подписчиков
              </h3>
              <p class="mt-2 text-gray-600">
                Привлечем реальных пользователей для развития вашего канала.
              </p>
            </div>
            <div class="service-card">
              <img
                src="/images/content-light.jpg"
                alt="Content"
                class="rounded-lg mb-4 w-full"
              />
              <h3 class="text-xl font-bold text-gray-800">Создание контента</h3>
              <p class="mt-2 text-gray-600">
                Мы создаем привлекательный и продающий контент.
              </p>
            </div>
          </div>
        </div>
      </section>

      <!-- Раздел Возможности -->
      <section id="features" class="py-16 bg-white shadow-inner">
        <div class="container mx-auto px-6 text-center">
          <h2 class="text-3xl font-bold text-blue-700">Возможности</h2>
          <p class="mt-4 text-gray-600">
            Наши инструменты помогут вам продвигать Telegram-канал с
            минимальными затратами.
          </p>
          <ul class="mt-8 space-y-4 text-lg font-medium">
            <li>📈 Увеличивайте охваты и набирайте подписчиков.</li>
            <li>📊 Для анализа доступны мощные аналитические инструменты.</li>
            <li>🤝 Возможность совместных проектов и рекламы.</li>
          </ul>
        </div>
      </section>
    </main>

    <!-- Нижний колонтитул -->
    <footer class="bg-gray-800 text-gray-300 py-6">
      <div class="container mx-auto text-center">
        <p>© 2025 Раскрутка Telegram. Все права защищены.</p>
      </div>
    </footer>
  </section>
</template>

<style scoped>
/* Основные стили */
html,
body {
  margin: 0;
  font-family: "Inter", sans-serif;
  scroll-behavior: smooth;
}

/* Контейнер шаблона */
.container {
  max-width: 1200px;
  margin: 0 auto;
}

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

/* Карточки услуг */
.service-card {
  background-color: #ffffff;
  border-radius: 0.5rem;
  padding: 1.5rem;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s, box-shadow 0.3s;
}
.service-card:hover {
  transform: translateY(-0.5rem);
  box-shadow: 0 15px 25px -3px rgba(0, 0, 0, 0.15);
}
</style>


