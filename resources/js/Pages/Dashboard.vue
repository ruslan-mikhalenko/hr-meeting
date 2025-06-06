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
import { onMounted, onBeforeUnmount, ref } from "vue";

// Функция для безопасного base64-кодирования с поддержкой Unicode
function b64EncodeUnicode(str) {
  return btoa(
    encodeURIComponent(str).replace(/%([0-9A-F]{2})/g, (match, p1) =>
      String.fromCharCode("0x" + p1)
    )
  );
}

// Параметры
const userId = 123;
const orderId = 456;
const channel = "Тест";

// Сформированный payload
const rawPayload = `site_join|user=${userId}|order=${orderId}|channel=${channel}`;
const payload = b64EncodeUnicode(rawPayload);

// Объявляем как `ref`, чтобы использовать в шаблоне и функциях
const channelUrl = ref(`https://t.me/TelegaBoostTest_bot?start=${payload}`);

// Кнопка ручного редиректа
function redirectToChannel() {
  console.log("Переход по кнопке на:", channelUrl.value);
  window.location.href = channelUrl.value;
}

// Автоматический редирект
onMounted(() => {
  console.log("Страница загружена. Подготовка к редиректу...");
  /* setTimeout(() => {
    console.log("Автоматический редирект на:", channelUrl.value);
    window.location.href = channelUrl.value;
  }, 15000); */

  addYandexMetrika();
});

// Яндекс.Метрика
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

onBeforeUnmount(() => {
  console.log("Уход со страницы.");
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
            <div class="px-6 pt-6 text-gray-800 text-center">
              <!-- <h2 class="text-xl font-bold mb-2">🚀 Как получить доступ</h2> -->

              <!-- <p class="text-base">Краткое наполнение контентом</p>

              <p class="text-base">
                🚀 Сейчас вы перейдёте в Telegram-бота,<br />
                👉 где, нажав кнопку <strong>«Start»</strong> внизу экрана, вы
                получите ссылку на канал Тест и сможете присоединиться к нему.
              </p>
              <p class="text-sm mt-2 text-gray-500">
                Если переход не начался автоматически — нажмите кнопку ниже.
              </p> -->

              <p class="text-base">Краткое наполнение контентом</p>

              <p class="text-base">
                🔔 Подпишитесь на канал в Telegram <br />
                Прямой переход к подписке займёт всего пару секунд!

                <p>👇 Нажмите «Start» ниже, чтобы подписаться на канал</p>
<p>📲 Нажмите кнопку «Start» — и бот мгновенно даст ссылку на наш канал «Тест».</p>
              </p>
              <p class="text-sm mt-2 text-gray-500">
                Если переход не начался автоматически — нажмите кнопку ниже.
              </p>
            </div>

            <div class="p-6 text-gray-900 text-center text-lg font-semibold">
              Редирект к подписке через 15 секунды...
            </div>
            <!-- Центральное расположение кнопки -->
            <div class="flex justify-center items-center mt-6">
              <button
                @click="redirectToChannel"
                class="px-6 py-3 bg-blue-600 text-white text-lg font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                Перейти к подписке на канал
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

