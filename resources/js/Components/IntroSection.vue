<template>
  <section id="intro" class="text-center py-20 bg-white shadow">
    <div class="container mx-auto px-6">
      <h1 class="text-4xl font-bold text-blue-700">
        Раскрутите ваш Telegram-канал быстро и просто.
      </h1>

      <p class="mt-4 text-gray-600">
        Мы увеличим вашу аудиторию, повысим охваты и создадим успешное
        сообщество любого масштаба.
      </p>

      <div class="mt-8 grid grid-cols-1 gap-8 md:grid-cols-3">
        <!-- Левый блок -->
        <div class="service-card md:col-span-2">
          <img
            src="/telega.jpg"
            alt="Telegram Promotion"
            class="mt-12 mx-auto w-[500px]"
          />
        </div>

        <!-- Правый блок: форма -->
        <div class="service-card">
          <h2 class="text-lg font-bold text-gray-800 mb-4">
            Быстрая заявка на обратную связь
          </h2>
          <form @submit.prevent="handleSubmit">
            <input
              type="text"
              name="name"
              placeholder="Ваше имя"
              class="form-input mb-4 text-black w-[100%]"
              v-model="name"
              required
            />
            <input
              type="text"
              name="phone"
              placeholder="Ваш телефон"
              class="form-input mb-4 text-black w-[100%]"
              v-model="phone"
              required
            />

            <!-- Капча -->
            <div class="mb-4">
              <div class="captcha-display">
                <strong class="captcha-text">{{ captchaText }}</strong>
              </div>
              <input
                type="text"
                v-model="captchaAnswer"
                placeholder="Ваш ответ"
                class="form-input mb-4 text-black w-[100%]"
                required
              />
            </div>

            <button
              type="submit"
              class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-all duration-300 h-[50px] button_my !w-[100%] !mt-0"
            >
              Отправить
            </button>
          </form>
          <p v-if="errorMessage" class="text-red-500 text-sm mt-2">
            {{ errorMessage }}
          </p>
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
</script>

<style scoped>
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
