<script setup>
import { ref, watch, onMounted } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import intlTelInput from "intl-tel-input";
import "intl-tel-input/build/css/intlTelInput.css";
import axios from "axios"; // Убедитесь, что эта библиотека установлена

// Управление состоянием мобильного меню
const isMenuOpen = ref(false);

// Состояние загрузки
const isSubmitting = ref(false);

const formData = ref({
  name: "",
  telegram: "",
  phone: "",
  email: "",
  service: "",
  customService: "",
});

const showCustomService = ref(false);

watch(
  () => formData.value.service,
  (newValue) => {
    showCustomService.value = newValue === "custom";
  }
);

// Привязка intl-tel-input
const inputRef = ref(null);

const initializeIntlTelInput = () => {
  if (inputRef.value) {
    intlTelInput(inputRef.value, {
      initialCountry: "ru",
      separateDialCode: true,
      geoIpLookup: (callback) => {
        fetch("http://ip-api.com/json")
          .then((response) => response.json())
          .then((data) => callback(data.countryCode))
          .catch(() => callback("us"));
      },
      separateDialCode: true,
      utilsScript: "node_modules/intl-tel-input/build/js/utils.js",
    });
  }
};

onMounted(() => {
  initializeIntlTelInput();
});

// Отправка формы
const handleSubmit = async () => {
  const phoneInput = inputRef.value?.value || formData.value.phone;

  try {
    isSubmitting.value = true; // Устанавливаем состояние загрузки
    const response = await axios.post(route("submit.form"), {
      ...formData.value,
      phone: phoneInput,
    });
    alert(response.data.message);

    // Очистка полей формы после успешной отправки
    formData.value = {
      name: "",
      telegram: "",
      phone: "",
      email: "",
      service: "",
      customService: "",
    };
    // Сброс значения в intl-tel-input
    if (inputRef.value) {
      inputRef.value.value = "";
    }
  } catch (error) {
    console.error("Ошибка:", error);
    alert("Ошибка при отправке заявки. Попробуйте позже.");
  } finally {
    isSubmitting.value = false; // Снимаем состояние загрузки
  }
};

// Функция для плавного скроллинга к заданным секциям
const handleScrollTo = (id) => {
  const target = document.querySelector(id);
  if (target) {
    target.scrollIntoView({
      behavior: "smooth",
      block: "start",
    });
    isMenuOpen.value = false;
  }
};
</script>

<template>
  <Head>
    <!-- Установите нужное название вкладки -->
    <title>!Раскрутка Telegram</title>
  </Head>
  <!-- Предзагрузчик -->
  <div v-if="isSubmitting" class="loading-overlay">
    <div class="spinner"></div>
  </div>

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
          <button @click="handleScrollTo('#order')" class="menu-item">
            Заявка
          </button>
          <!--  <button @click="handleScrollTo('#steps')" class="menu-item">
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
          <!-- <button @click="handleScrollTo('#steps')" class="mobile-menu-item">
            Как начать
          </button>
          <button @click="handleScrollTo('#pricing')" class="mobile-menu-item">
            Тарифы
          </button> -->
          <button @click="handleScrollTo('#order')" class="mobile-menu-item">
            Заявка
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
    <main class="flex-grow pt-16 lg:pt-16">
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

          <img
            src="/telega-main-1.webp"
            alt="Telegram Promotion"
            class="mt-12 mx-auto w-[500px]"
          />
          <!-- <img
            src="/images/telegram-intro-light.jpg"
            alt="Telegram Promotion"
            class="mt-6 mx-auto rounded-lg shadow-lg w-full md:w-3/4 lg:w-1/2"
          /> -->
        </div>
      </section>
      <div class="section-divider">
        <span class="divider-text">★</span>
      </div>

      <!-- Раздел Услуги -->
      <section id="services" class="bg-gray-50 py-16">
        <div class="container mx-auto px-6 text-center">
          <h2 class="text-3xl font-bold text-blue-700">Наши Услуги</h2>
          <p class="mt-4 text-gray-600">
            Мы помогаем вам реализовать идеи и создать успешный Telegram-канал.
          </p>
          <div class="mt-8 grid grid-cols-1 gap-8 md:grid-cols-3">
            <div class="service-card">
              <!-- <img
                src="/images/setup-light.jpg"
                alt="Setup"
                class="rounded-lg mb-4 w-full"
              /> -->
              <div class="icon-container">
                <!-- Иконка -->
                <!-- Иконка шестеренки -->
                <svg
                  class="gear-icon"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24"
                >
                  <path
                    d="M19.14 12.94c.03-.3.06-.59.06-.94s-.03-.64-.06-.94l2.03-1.58c.18-.14.22-.39.1-.59l-1.91-3.31c-.12-.21-.38-.28-.59-.22l-2.39.96c-.5-.39-1.05-.72-1.64-.96l-.36-2.54A.485.485 0 0 0 14.03 3h-4.06c-.24 0-.44.17-.47.41l-.36 2.54c-.59.24-1.14.57-1.64.96l-2.39-.96a.485.485 0 0 0-.59.22l-1.91 3.31a.485.485 0 0 0 .1.59l2.03 1.58c-.03.3-.06.63-.06.94s.03.64.06.94l-2.03 1.58a.485.485 0 0 0-.1.59l1.91 3.31c.12.21.37.28.59.22l2.39-.96c.5.39 1.05.72 1.64.96l.36 2.54c.03.24.23.41.47.41h4.06c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.14-.57 1.64-.96l2.39.96c.21.06.46-.02.59-.22l1.91-3.31c.12-.21.07-.45-.1-.59l-2.03-1.58ZM12 15.25c-1.79 0-3.25-1.46-3.25-3.25S10.21 8.75 12 8.75s3.25 1.46 3.25 3.25-1.46 3.25-3.25 3.25Z"
                  />
                </svg>
              </div>
              <h3 class="text-xl font-bold text-gray-800">Создание канала</h3>
              <p class="mt-2 text-gray-600">
                Оформим ваш Telegram-канал профессионально
              </p>
            </div>
            <div class="service-card">
              <!-- <img
                src="/images/subscribers-light.jpg"
                alt="Subscribers"
                class="rounded-lg mb-4 w-full"
              /> -->
              <div class="icon-container">
                <svg
                  class="gear-icon"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24"
                >
                  <path
                    d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zM8 11c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V20h14v-3.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 2.02 1.97 3.45V20h6v-3.5c0-2.33-4.67-3.5-7-3.5z"
                  />
                </svg>
              </div>
              <h3 class="text-xl font-bold text-gray-800">
                Привлечение подписчиков
              </h3>
              <p class="mt-2 text-gray-600">
                Привлечем реальных пользователей для развития вашего канала
              </p>
            </div>
            <div class="service-card">
              <!--  <img
                src="/images/content-light.jpg"
                alt="Content"
                class="rounded-lg mb-4 w-full"
              /> -->
              <div class="icon-container">
                <svg
                  class="gear-icon"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24"
                >
                  <path
                    d="M3 21v-2.75l11.33-11.33 2.75 2.75L5.75 21H3zm14.91-11.91l-2.75-2.75 2.2-2.2a1.41 1.41 0 0 1 2 0l.75.75a1.41 1.41 0 0 1 0 2l-2.2 2.2z"
                  />
                </svg>
              </div>
              <h3 class="text-xl font-bold text-gray-800">Создание контента</h3>
              <p class="mt-2 text-gray-600">
                Мы создаем привлекательный и продающий контент
              </p>
            </div>
          </div>
        </div>
      </section>
      <div class="section-divider">
        <span class="divider-text">★</span>
      </div>
      <!-- Раздел Возможности -->
      <section id="features" class="py-16 bg-white shadow-inner">
        <div class="container mx-auto px-6 text-center">
          <h2 class="text-3xl font-bold text-blue-700">Возможности</h2>
          <p class="mt-4 text-gray-600">
            Наши инструменты помогут вам продвигать Telegram-канал с
            минимальными затратами.
          </p>
          <div class="mt-8">
            <ul class="space-y-4 text-lg font-medium">
              <li>📚 Тематическая Контентная поддержка</li>
              <li>📈 Увеличивайте охваты и набирайте подписчиков</li>
              <li>📊 Для анализа доступны мощные аналитические инструменты</li>
              <li>🤝 Возможность совместных проектов и рекламы</li>
            </ul>
          </div>
        </div>
      </section>
      <div class="section-divider" id="order">
        <span class="divider-text">★</span>
      </div>

      <!-- Раздел Заявка -->
      <section class="text-center py-10 bg-white shadow">
        <div class="container mx-auto px-6">
          <!-- Форма заявки на услугу -->
          <div class="mt-0 bg-white pb-14 rounded-lg shadow-md mb-16">
            <h2 class="text-3xl font-bold text-blue-700">
              Оставьте заявку на услугу
            </h2>
            <p class="text-gray-600 mb-6">
              Заполните форму, и мы свяжемся с вами в ближайшее время!
            </p>
            <form @submit.prevent="handleSubmit">
              <div
                class="grid grid-cols-1 gap-6 md:grid-cols-2 my-16 p-0 md:p-10"
              >
                <!-- Поле "Ваше имя" -->
                <div>
                  <label
                    class="block text-sm font-medium text-gray-600 mb-2"
                    for="name"
                  >
                    Ваше имя <span class="text-red-500">*</span>
                  </label>
                  <input
                    id="name"
                    type="text"
                    v-model="formData.name"
                    class="w-full border-gray-300 rounded-md shadow-sm px-4 py-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Введите ваше имя"
                    required
                  />
                </div>
                <!-- Поле "Телеграмм аккаунт" -->
                <div>
                  <label
                    class="block text-sm font-medium text-gray-600 mb-2"
                    for="telegram"
                  >
                    Телеграмм аккаунт
                  </label>
                  <input
                    id="telegram"
                    type="text"
                    v-model="formData.telegram"
                    class="w-full border-gray-300 rounded-md shadow-sm px-4 py-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="@username"
                  />
                </div>
                <!-- Поле "Телефон" -->
                <div>
                  <label
                    class="block text-sm font-medium text-gray-600 mb-2"
                    for="phone"
                    >Телефон</label
                  >
                  <input
                    id="phone"
                    type="tel"
                    ref="inputRef"
                    v-model="formData.phone"
                    class="w-full border-gray-300 rounded-md shadow-sm px-4 py-2"
                    placeholder=""
                  />
                </div>
                <!-- Поле "Email" -->
                <div>
                  <label
                    class="block text-sm font-medium text-gray-600 mb-2"
                    for="email"
                  >
                    Email <span class="text-red-500">*</span>
                  </label>
                  <input
                    id="email"
                    type="email"
                    v-model="formData.email"
                    class="w-full border-gray-300 rounded-md shadow-sm px-4 py-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="example@email.com"
                    required
                  />
                </div>
                <!-- Поле "Услуга" -->
                <div class="md:col-span-2">
                  <label
                    class="block text-sm font-medium text-gray-600 mb-2"
                    for="service"
                    >Услуга<span class="text-red-500">*</span></label
                  >
                  <select
                    id="service"
                    v-model="formData.service"
                    class="w-full border-gray-300 rounded-md shadow-sm px-4 py-2 focus:ring-blue-500 focus:border-blue-500"
                    required
                  >
                    <option value="" disabled selected>
                      Выберите услугу...
                    </option>
                    <option value="Создание Telegram-канала (настройка среды)">
                      Создание Telegram-канала (настройка среды)
                    </option>
                    <option value="Создание Telegram-канала + раскрутка">
                      Создание Telegram-канала + раскрутка
                    </option>
                    <option
                      value="Создание Telegram-канала + раскрутка + аналитика"
                    >
                      Создание Telegram-канала + раскрутка + аналитика
                    </option>
                    <option value="Своя услуга по Telegram">Своя услуга</option>
                  </select>
                </div>
                <!-- Поле для описания "Своя услуга" -->
                <div v-if="showCustomService" class="md:col-span-2">
                  <label
                    class="block text-sm font-medium text-gray-600 mb-2"
                    for="customService"
                  >
                    Опишите услугу
                  </label>
                  <textarea
                    id="customService"
                    v-model="formData.customService"
                    rows="3"
                    class="w-full border-gray-300 rounded-md shadow-sm px-4 py-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Опишите, что именно вам нужно"
                    required
                  ></textarea>
                </div>
              </div>
              <!-- Кнопка отправки -->
              <div class="mt-6">
                <button type="submit" class="btn btn-primary w-[200px] p-10">
                  Отправить заявку
                </button>
              </div>
            </form>
          </div>
        </div>
      </section>
    </main>

    <!-- Нижний колонтитул -->
    <footer class="bg-gray-800 text-gray-300 py-6" id="contacts">
      <div class="container mx-auto text-center">
        <p>© 2025 Раскрутка Telegram. Все права защищены.</p>
      </div>
    </footer>
  </section>
</template>

<style>
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

/***** Настройка для intl-tel-input *****/
.iti {
  display: block !important;
  width: 100% !important;
}

.iti * {
  box-sizing: border-box;
}

.iti__selected-flag {
  height: 100%;
}

input[type="tel"] {
  width: 100% !important; /* Убедимся, что ширина поля остается на всю доступную ширину */
  padding-left: 50px; /* Учитываем иконку страны (флаг) */
}

/* Обязательные поля */
input:required,
select:required,
textarea:required {
  background-color: #f5fbff; /* Легкий розовый оттенок */
  border-color: #349dff; /* Красная рамка */
}

section#features ul {
  display: inline-block; /* Делаем `<ul>` блочным элементом, зависящим по ширине от содержимого */
  text-align: left; /* Выравнивание внутри списка по левому краю */
  padding: 0; /* Убираем внутренние отступы списка */
}

.section-divider {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 4rem 0; /* Отступы сверху и снизу */
  position: relative;
}

.section-divider::before,
.section-divider::after {
  content: "";
  flex: 1;
  border-bottom: 2px solid #e5e7eb; /* Линия разделителя */
  margin: 0 1rem; /* Пробел между линией и текстом/иконкой */
}

.divider-text {
  font-size: 1.5rem;
  color: #2563eb; /* Синий цвет текста */
  font-weight: bold;
  text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2); /* Легкая тень для текста */
}

/* Обновленный стиль для контейнера иконок */
.icon-container {
  background-color: #ffffff; /* Светло-голубой фон */
  border-radius: 50%; /* Округлая форма */
  width: 80px;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto; /* Центровка */
  box-shadow: 0 4px 10px rgba(255, 255, 255, 0.1); /* Легкая тень */
}

.gear-icon {
  width: 64px;
  height: 64px;
  fill: #007bff;
  stroke: #fdfdfd;
  stroke-width: 1;
  filter: drop-shadow(2px 4px 6px rgba(255, 255, 255, 0.5));
}

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 5px solid rgba(255, 255, 255, 0.6);
  border-top: 5px solid #fff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 768px) {
  /* Стили для мобильных устройств */
  section#features ul {
    text-align: center; /* Выравнивание внутри списка по левому краю */
    font-weight: normal;
  }
}
</style>


