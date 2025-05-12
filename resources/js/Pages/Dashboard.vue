<!-- <script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
</script>

<template>
  <Head title="Dashboard" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Dashboard
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">You're logged in!</div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
 -->

<script setup>
import { onMounted, onBeforeUnmount } from "vue";

// URL для редиректа
const channelUrl = "https://t.me/+GBRMKva5zohjNjMy"; // Укажите ссылку на ваш канал

// Автоматический редирект, выполняющийся при загрузке страницы
onMounted(() => {
  console.log("Страница загружена. Подготовка к редиректу...");
  setTimeout(() => {
    console.log("Автоматический редирект на:", channelUrl);
    window.location.href = channelUrl; // Редирект через 5 секунд
  }, 5000);

  // Добавление метрики
  addYandexMetrika();
});

// Функция добавления Яндекс.Метрики
function addYandexMetrika() {
  (function (m, e, t, r, i, k, a) {
    m[i] =
      m[i] ||
      function () {
        (m[i].a = m[i].a || []).push(arguments);
      };
    m[i].l = 1 * new Date();
    for (var j = 0; j < document.scripts.length; j++) {
      if (document.scripts[j].src === r) {
        return;
      }
    }
    (k = e.createElement(t)),
      (a = e.getElementsByTagName(t)[0]),
      (k.async = 1),
      (k.src = r),
      a.parentNode.insertBefore(k, a);
  })(window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

  ym(101725200, "init", {
    clickmap: true,
    trackLinks: true,
    accurateTrackBounce: true,
  });
}

// При необходимости можно очищать Метрику при размонтировании компонента
onBeforeUnmount(() => {
  console.log("Страница покидается. Можно очистить Метрику, если нужно.");
});
</script>

<template>
  <!-- Главный контейнер -->
  <div class="flex flex-col min-h-screen">
    <!-- Основной контент -->
    <main class="flex-grow">
      <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 text-center text-lg font-semibold">
              Редирект на канал через 5 секунды...
            </div>
            <!-- Центральное расположение кнопки -->
            <div class="flex justify-center items-center mt-6">
              <button
                @click="redirectToChannel"
                class="px-6 py-3 bg-blue-600 text-white text-lg font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                Перейти на канал
              </button>
            </div>
          </div>
        </div>
      </div>
    </main>
    <!-- Футер -->
    <footer
      class="border-t border-gray-300 py-4 text-center text-sm text-gray-600"
    >
      <p>ИП Тест • ИНН: 43242472575</p>
    </footer>
  </div>
</template>

