<template>
  <section id="intro" class="text-center py-20 bg-[white] shadow">
    <div class="container mx-auto px-6">
      <div class="mt-8 grid grid-cols-1 gap-8 md:grid-cols-3">
        <!-- Левый блок -->
        <div
          class="service-card md:col-span-2 bg-white p-8 pt-0 rounded-lg shadow-lg"
        >
          <h1 class="text-4xl font-bold text-blue-700 mb-4">
            Повышение эффективности работы с персоналом
          </h1>
          <hr class="my-6 border-gray-300" />

          <img
            src="/telega.jpg"
            alt="Phone Frame"
            class="max-w-[200px] md:max-w-[250px] z-10"
          />

          <ul class="list-disc list-inside text-left space-y-4">
            <li class="flex items-start">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-6 w-6 text-blue-600 mr-2"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 13l4 4L19 7"
                />
              </svg>
              <span>Инновационные решения для аутсорса и аутстаффинга.</span>
            </li>
            <li class="flex items-start">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-6 w-6 text-blue-600 mr-2"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 13l4 4L19 7"
                />
              </svg>
              <span>Оптимизация бизнес-процессов и снижение затрат.</span>
            </li>
            <li class="flex items-start">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-6 w-6 text-blue-600 mr-2"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 13l4 4L19 7"
                />
              </svg>
              <span>Сосредоточение на важнейших задачах вашего бизнеса.</span>
            </li>
            <li class="flex items-start">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-6 w-6 text-blue-600 mr-2"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 13l4 4L19 7"
                />
              </svg>
              <span>Системный подход к управлению персоналом.</span>
            </li>
            <li class="flex items-start">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-6 w-6 text-blue-600 mr-2"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 13l4 4L19 7"
                />
              </svg>
              <span>Профессиональное развитие и обучение сотрудников.</span>
            </li>
          </ul>

          <p class="mt-6 text-blue-500 font-semibold">
            Организуйте встречу с нами, чтобы обсудить детали!
          </p>
        </div>

        <!-- Правый блок: форма -->
        <div class="service-card">
          <div>
            <div class="relative flex justify-center items-center py-2">
              <!-- <img
                src="/mockup-phone.png"
                alt="Phone Frame"
                class="max-w-[200px] md:max-w-[250px] z-10"
                style="visibility: hidden"
              /> -->

              <!-- Слайдер, вставленный в экран телефона -->
              <div
                class="absolute z-20"
                :style="{
                  width: isMobile ? '240px' : '500px',
                  height: isMobile ? '520px' : '375px',
                  top: isMobile ? '0px' : '0px',
                }"
              >
                <a-carousel autoplay effect="fade" dots class="w-full h-full">
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
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from "vue";

// Состояния формы
const name = ref("");
const phone = ref("");
const captchaAnswer = ref("");
const errorMessage = ref("");
const captchaText = ref("");

// Генерация капчи
const generateCaptcha = () => {
  const characters =
    "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
  let captcha = "";
  for (let i = 0; i < 4; i++) {
    captcha += characters.charAt(Math.floor(Math.random() * characters.length));
  }
  captchaText.value = captcha;
};

// Обработка формы
const handleSubmit = () => {
  if (captchaAnswer.value.trim() === "") {
    errorMessage.value = "Пожалуйста, введите ответ на капчу.";
  } else if (captchaAnswer.value !== captchaText.value) {
    errorMessage.value = "Неправильный ответ на капчу. Попробуйте еще раз.";
    captchaAnswer.value = "";
    generateCaptcha();
  } else {
    errorMessage.value = "";
    console.log("Форма отправлена:", {
      name: name.value,
      phone: phone.value,
    });
    // Можно добавить отправку данных на сервер
  }
};

onMounted(() => {
  generateCaptcha();
});

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

<style scoped>
.bg-slide-0 {
  background-image: url("/slides/2.png");
}
.bg-slide-1 {
  background-image: url("/slides/2.png");
}
.bg-slide-2 {
  background-image: url("/slides/2.png");
}

.bg-slide-3 {
  background-image: url("/slides/2.png");
}
.bg-slide-4 {
  background-image: url("/slides/2.png");
}

/* Минимальные стили для капчи */
.captcha-display {
  background: #f0f0f0;
  padding: 10px;
  margin-bottom: 8px;
  text-align: center;
  font-size: 20px;
  letter-spacing: 3px;
  border-radius: 6px;
  font-weight: bold;
  color: #1e40af;
}
</style>
