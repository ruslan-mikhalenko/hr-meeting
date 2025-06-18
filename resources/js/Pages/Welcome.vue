<script setup>
import { ref, watch, onMounted } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import intlTelInput from "intl-tel-input";
import "intl-tel-input/build/css/intlTelInput.css";
import axios from "axios"; // Убедитесь, что эта библиотека установлена

import GuestLayout from "@/Layouts/GuestLayout.vue";

import Navbar from "@/Components/Navbar.vue";
import IntroSection from "@/Components/IntroSection.vue";
import TimelinePromo from "@/Components/TimelinePromo.vue";

import {
  FormOutlined,
  PhoneOutlined,
  RocketOutlined,
  LineChartOutlined,
} from "@ant-design/icons-vue";

import { Badge } from "ant-design-vue";
import { CheckOutlined } from "@ant-design/icons-vue";

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
    console.log("Service selected:", newValue); // Отладка
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

/* const slides = ref([
  {
    title: "Создайте свой Telegram-канал",
    subtitle: "Начните бесплатно и быстро.",
  },
  {
    title: "Аналитика и отчёты",
    subtitle: "Понимайте, что работает.",
  },
  {
    title: "Рассылки и подписчики",
    subtitle: "Простые инструменты роста.",
  },
]); */

/* const slides = ref([
  {
    title: "Создайте свой телеграм-канал",
    subtitle: "Начните бесплатно и быстро",
  },
  {
    title: "Оценивайте аналитику",
    subtitle: "Делайте выводы по продвигаемому проекту и подбирайте стратегию",
  },
  {
    title: "Подключайте Яндекс метрику на события по каналу",
    subtitle:
      "Используйте цели для оптимизации рекламных компаниц в Яндекс Директ ",
  },
  {
    title: "Собирайте базу подписчиков",
    subtitle:
      "Обеспечивайте взамимодейстиве сними для ковышения конверсий своего бизнеса",
  },
  {
    title:
      "Создавайте посадочные страницы/лендинги для продвижения свтоего канала",
    subtitle:
      "Расмещайте в сети интренет стницы на канал и оценивайте аналику по переходам и подпискам ",
  },
]); */

const slides = ref([
  {
    title: "Создайте телеграм-канал",
    subtitle: "Быстрый и бесплатный старт!",
  },
  {
    title: "Анализируйте результаты",
    subtitle: "Оценивайте эффективность и выбирайте лучшие стратегии.",
  },
  {
    title: "Подключите Яндекс.Метрику",
    subtitle: "Оптимизируйте рекламу с помощью целей и событий.",
  },
  {
    title: "Собирайте базу подписчиков",
    subtitle: "Увеличивайте конверсии через вовлеченность и взаимодействие.",
  },
  {
    title: "Создавайте лендинги для продвижения",
    subtitle: "Привлекайте внимание к вашему каналу и анализируйте переходы.",
  },
]);

// Для адаптивности
const isMobile = ref(false);
onMounted(() => {
  isMobile.value = window.innerWidth < 768;
});
</script>

<template>
  <GuestLayout>
    <Head>
      <!-- Установите нужное название вкладки -->
      <title>!Раскрутка Telegram</title>
    </Head>
    <!-- Предзагрузчик -->
    <div v-if="isSubmitting" class="loading-overlay">
      <div class="spinner"></div>
    </div>

    <section class="min-h-screen flex flex-col bg-gray-100 text-gray-800">
      <!-- Основной контент -->
      <main class="flex-grow pt-16 lg:pt-16">
        <!-- Раздел Вступление -->
        <IntroSection />

        <section id="services" class="bg-gray-50 py-16">
          <div class="container mx-auto px-6 text-center">
            <h2 class="text-3xl font-bold text-blue-700">В чём суть</h2>
            <p class="mt-4 text-gray-600">
              Мы помогаем вам реализовать идеи и создать успешный
              Telegram-канал.
            </p>
            <div class="mt-8 grid grid-cols-1 gap-8 md:grid-cols-2">
              <div class="service-card flex">
                <!-- <img
                src="/2.png"
                alt="Telegram Promotion"
                class="mt-12 mx-auto w-[500px]"
              /> -->

                <ul class="space-y-8 justify-center items-center">
                  <!-- <li class="flex items-center space-x-4">
                  <Badge
                    color="#52c41a"
                    class="flex-shrink-0 rounded-full bg-green-100 w-8 h-8 flex items-center justify-center"
                  >
                    <CheckOutlined style="font-size: 20px; color: #52c41a" />
                  </Badge>
                  <span class="text-gray-800 text-xl text-left">
                    <b>Создайте канал, если его ещё нет</b> – сделайте это сами
                    или мы поможем
                  </span>
                </li> -->
                  <li class="flex items-center space-x-4">
                    <Badge
                      color="#52c41a"
                      class="flex-shrink-0 rounded-full bg-green-100 w-8 h-8 flex items-center justify-center"
                    >
                      <CheckOutlined style="font-size: 20px; color: #52c41a" />
                    </Badge>
                    <span class="text-gray-800 text-xl text-left">
                      Подключаем ваш канал к нашей системе – отправьте заявку и
                      начните работу.
                    </span>
                  </li>
                  <li class="flex items-center space-x-4">
                    <Badge
                      color="#52c41a"
                      class="flex-shrink-0 rounded-full bg-green-100 w-8 h-8 flex items-center justify-center"
                    >
                      <CheckOutlined style="font-size: 20px; color: #52c41a" />
                    </Badge>
                    <span class="text-gray-800 text-xl text-left">
                      Анализируйте данные – получайте простую и понятную
                      аналитику в личном кабинете и в Яндекс Метрике.
                    </span>
                  </li>

                  <li class="flex items-center space-x-4">
                    <Badge
                      color="#52c41a"
                      class="flex-shrink-0 rounded-full bg-green-100 w-8 h-8 flex items-center justify-center"
                    >
                      <CheckOutlined style="font-size: 20px; color: #52c41a" />
                    </Badge>
                    <span class="text-gray-800 text-xl text-left">
                      Возможность рассылок присоединившимся к вашему каналу–
                      увеличьте интерактивность со своей аудиторией.
                    </span>
                  </li>

                  <li class="flex items-center space-x-4">
                    <Badge
                      color="#52c41a"
                      class="flex-shrink-0 rounded-full bg-green-100 w-8 h-8 flex items-center justify-center"
                    >
                      <CheckOutlined style="font-size: 20px; color: #52c41a" />
                    </Badge>
                    <span class="text-gray-800 text-xl text-left">
                      Рекламируйте через Яндекс Директ – создавайте лендинги для
                      продвижения и оптимизируйте рекламные компании
                    </span>
                  </li>
                </ul>
              </div>
              <div>
                <div class="relative flex justify-center items-center py-2">
                  <!-- Изображение смартфона -->
                  <!-- <img
                  src="/mockup-phone.png"
                  alt="Phone Frame"
                  class="max-w-[250px] md:max-w-[350px] z-10"
                  style="visibility: hidden"
                /> -->

                  <!-- Слайдер, вставленный в экран телефона -->
                  <div
                    class="absolute z-20"
                    :style="{
                      width: isMobile ? '240px' : '300px',
                      height: isMobile ? '520px' : '580px',
                      top: isMobile ? '0px' : '0px',
                    }"
                  >
                    <a-carousel
                      autoplay
                      effect="fade"
                      dots
                      class="w-full h-full"
                    >
                      <div
                        v-for="(slide, index) in slides"
                        :key="index"
                        :class="[
                          'h-full w-full bg-cover bg-center text-white flex items-center justify-center px-4',
                          `bg-slide-${index}`,
                        ]"
                      >
                        <div
                          class="p-4 rounded-xl text-center h-[520px] pt-[270px]"
                        >
                          <h3
                            class="text-base font-bold mb-2 text-[1.7rem] leading-[1.9rem]"
                          >
                            {{ slide.title }}
                          </h3>
                          <p class="text-sm mt-[20px]">{{ slide.subtitle }}</p>
                        </div>
                      </div>
                    </a-carousel>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="py-24 px-6 max-w-5xl mx-auto">
          <h2 class="text-5xl font-extrabold text-center text-indigo-700 mb-16">
            Оказываем содействие
          </h2>
          <p class="text-center max-w-4xl mx-auto text-xl text-gray-700 mb-20">
            Всё что нужно для успешного продвижения вашего Telegram-канала в
            одном удобном сервисе.
          </p>
          <div
            class="flex flex-col space-y-20 md:space-y-0 md:grid md:grid-cols-2 md:gap-x-20"
          >
            <div class="flex items-center space-x-8 md:order-1">
              <img
                src="/images/analytics.svg"
                alt="Аналитика"
                class="w-48 h-48 object-contain"
              />
              <div>
                <h3 class="text-4xl font-bold mb-3 text-gray-900">
                  Простая аналитика
                </h3>
                <p class="text-gray-600 text-lg max-w-md">
                  Отслеживайте ключевые показатели канала в удобном интерфейсе.
                </p>
              </div>
            </div>

            <div
              class="flex items-center space-x-8 md:order-2 md:flex-row-reverse"
            >
              <img
                src="/images/report.svg"
                alt="Отчёты"
                class="w-48 h-48 object-contain"
              />
              <div>
                <h3 class="text-4xl font-bold mb-3 text-gray-900">
                  PDF отчёты
                </h3>
                <p class="text-gray-600 text-lg max-w-md">
                  Формируйте понятные отчёты и делитесь ими с командой и
                  клиентами.
                </p>
              </div>
            </div>

            <div class="flex items-center space-x-8 md:order-3">
              <img
                src="/images/subscribers.svg"
                alt="Подписчики"
                class="w-48 h-48 object-contain"
              />
              <div>
                <h3 class="text-4xl font-bold mb-3 text-gray-900">
                  Сбор подписчиков
                </h3>
                <p class="text-gray-600 text-lg max-w-md">
                  Увеличивайте аудиторию с помощью удобных инструментов.
                </p>
              </div>
            </div>

            <div
              class="flex items-center space-x-8 md:order-4 md:flex-row-reverse"
            >
              <img
                src="/images/messaging.svg"
                alt="Рассылки"
                class="w-48 h-48 object-contain"
              />
              <div>
                <h3 class="text-4xl font-bold mb-3 text-gray-900">
                  Рассылки в Telegram
                </h3>
                <p class="text-gray-600 text-lg max-w-md">
                  Делайте персональные рассылки подписчикам в личку.
                </p>
              </div>
            </div>

            <!-- Новый блок 5 -->
            <div class="flex items-center space-x-8 md:order-5">
              <img
                src="/images/seo.svg"
                alt="SEO"
                class="w-48 h-48 object-contain"
              />
              <div>
                <h3 class="text-4xl font-bold mb-3 text-gray-900">
                  SEO оптимизация
                </h3>
                <p class="text-gray-600 text-lg max-w-md">
                  Достигайте высоких позиций в поисковых системах.
                </p>
              </div>
            </div>

            <!-- Новый блок 6 -->
            <div
              class="flex items-center space-x-8 md:order-6 md:flex-row-reverse"
            >
              <img
                src="/images/support.svg"
                alt="Поддержка"
                class="w-48 h-48 object-contain"
              />
              <div>
                <h3 class="text-4xl font-bold mb-3 text-gray-900">
                  Круглосуточная поддержка
                </h3>
                <p class="text-gray-600 text-lg max-w-md">
                  Получайте помощь в любое время суток от нашей команды.
                </p>
              </div>
            </div>
          </div>
        </section>

        <div class="mx-auto my-20">
          <TimelinePromo />
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
                      <option
                        value="Создание Telegram-канала (настройка среды)"
                      >
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
                      <option value="custom">Своя услуга</option>
                      <!-- Здесь нужно заменить -->
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
  </GuestLayout>
</template>

<style scoped>
.bg-slide-0 {
  background-image: url("/slides/pict1.png");
}
.bg-slide-1 {
  background-image: url("/slides/pict1.png");
}
.bg-slide-2 {
  background-image: url("/slides/pict1.png");
}

.bg-slide-3 {
  background-image: url("/slides/pict1.png");
}
.bg-slide-4 {
  background-image: url("/slides/pict1.png");
}

/* Добавим отступы для таймлайна */
.a-timeline-item-content {
  max-width: 600px;
}

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

input {
  height: 50px;
}

input[type="tel"] {
  width: 100% !important; /* Убедимся, что ширина поля остается на всю доступную ширину */
  padding-left: 50px; /* Учитываем иконку страны (флаг) */
}

/* Обязательные поля */
input:required,
select:required,
textarea:required {
  background-color: #f9fcff; /* Легкий розовый оттенок */
  border-color: #616161; /* Красная рамка */
  height: 45px;
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


