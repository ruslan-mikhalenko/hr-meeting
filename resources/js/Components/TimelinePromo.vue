<template>
  <a-card bordered="false" class="rounded-2xl shadow-xl bg-white p-8 mx-auto">
    <h2 class="text-4xl font-extrabold text-center text-blue-600 mb-12">
      Что надо сделать
    </h2>

    <div class="relative">
      <!-- Вертикальная линия по центру для десктопа -->
      <div
        class="hidden md:block absolute top-0 left-1/2 -translate-x-1/2 h-full border-l-4 border-blue-200"
      ></div>

      <!-- Шаг 1 -->
      <div class="flex flex-col md:flex-row items-center mb-20 md:mb-28">
        <!-- Текст слева -->
        <div class="md:w-1/2 flex items-center md:justify-end pr-0 md:pr-8">
          <div class="max-w-md px-4 md:px-0">
            <div class="flex items-center mb-4">
              <FormOutlined style="font-size: 32px; color: #1890ff" />
              <h3 class="ml-4 text-3xl font-bold text-gray-800">
                1. Оставьте заявку
              </h3>
            </div>
            <p class="text-gray-600 text-lg">
              Просто заполните короткую форму и введите капчу.
            </p>
          </div>
        </div>
        <!-- Картинка справа, под текстом на мобилке -->
        <div class="md:w-1/2 pl-0 md:pl-8 mt-6 md:mt-0 w-full">
          <img
            src="/images/step1.jpg"
            alt="Шаг 1"
            class="rounded-lg object-cover w-full h-64 md:h-80"
          />
        </div>
      </div>

      <!-- Шаг 2 -->
      <div class="flex flex-col md:flex-row items-center mb-20 md:mb-28">
        <!-- Картинка слева, над текстом на мобилке -->
        <div class="md:w-1/2 pr-0 md:pr-8 order-2 md:order-1 w-full">
          <img
            src="/images/step2.jpg"
            alt="Шаг 2"
            class="rounded-lg object-cover w-full h-64 md:h-80"
          />
        </div>
        <!-- Текст справа -->
        <div
          class="md:w-1/2 flex items-center md:justify-start pl-0 md:pl-8 order-1 md:order-2 px-4 md:px-0 mt-6 md:mt-0"
        >
          <div class="max-w-md">
            <div class="flex items-center mb-4">
              <PhoneOutlined style="font-size: 32px; color: #52c41a" />
              <h3 class="ml-4 text-3xl font-bold text-gray-800">
                2. Мы свяжемся с вами
              </h3>
            </div>
            <p class="text-gray-600 text-lg">
              Наш специалист уточнит цели и даст рекомендации.
            </p>
          </div>
        </div>
      </div>

      <!-- Шаг 3 -->
      <div class="flex flex-col md:flex-row items-center mb-20 md:mb-28">
        <!-- Текст слева -->
        <div
          class="md:w-1/2 flex items-center md:justify-end pr-0 md:pr-8 px-4 md:px-0"
        >
          <div class="max-w-md">
            <div class="flex items-center mb-4">
              <RocketOutlined style="font-size: 32px; color: #722ed1" />
              <h3 class="ml-4 text-3xl font-bold text-gray-800">
                3. Запуск продвижения
              </h3>
            </div>
            <p class="text-gray-600 text-lg">
              Мы начинаем оформление и привлечение подписчиков.
            </p>
          </div>
        </div>
        <!-- Картинка справа -->
        <div class="md:w-1/2 pl-0 md:pl-8 mt-6 md:mt-0 w-full">
          <img
            src="/images/step3.jpg"
            alt="Шаг 3"
            class="rounded-lg object-cover w-full h-64 md:h-80"
          />
        </div>
      </div>

      <!-- Шаг 4 -->
      <div class="flex flex-col md:flex-row items-center mb-20 md:mb-28">
        <!-- Картинка слева -->
        <div class="md:w-1/2 pr-0 md:pr-8 order-2 md:order-1 w-full">
          <img
            src="/images/step4.jpg"
            alt="Шаг 4"
            class="rounded-lg object-cover w-full h-64 md:h-80"
          />
        </div>
        <!-- Текст справа -->
        <div
          class="md:w-1/2 flex items-center md:justify-start pl-0 md:pl-8 order-1 md:order-2 px-4 md:px-0 mt-6 md:mt-0"
        >
          <div class="max-w-md">
            <div class="flex items-center mb-4">
              <LineChartOutlined style="font-size: 32px; color: #faad14" />
              <h3 class="ml-4 text-3xl font-bold text-gray-800">
                4. Рост и результат
              </h3>
            </div>
            <p class="text-gray-600 text-lg">
              Вы отслеживаете прогресс в режиме реального времени.
            </p>
          </div>
        </div>
      </div>
    </div>
  </a-card>
</template>

<script setup>
import {
  FormOutlined,
  PhoneOutlined,
  RocketOutlined,
  LineChartOutlined,
} from "@ant-design/icons-vue";
</script>

<style scoped>
@media (max-width: 767px) {
  /* Скрыть вертикальную линию на мобилках */
  .relative > div.border-l-4 {
    display: none !important;
  }
}
</style>

