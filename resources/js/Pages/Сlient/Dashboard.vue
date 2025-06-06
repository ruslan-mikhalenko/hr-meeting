<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import {
  ref,
  computed,
  watch,
  onMounted,
  onUnmounted,
  defineProps,
  defineEmits,
} from "vue";

import Modal from "@/Components/ModalCrud.vue";

import Loader from "@/Components/Loader.vue"; // Импортируем компонент предзагрузчика

import { EyeOutlined, EyeInvisibleOutlined } from "@ant-design/icons-vue"; // Импортируем иконки

const props = defineProps({
  client_rights: {
    type: Boolean,
  },
  user_auth: {
    type: Array,
    required: true,
  },

  projects: {
    type: Array,
    required: true,
  },
});
</script>

<template>
  <Head title="Клиенты" />

  <AuthenticatedLayout :user_auth="props.user_auth">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        <SelectOutlined :style="{ fontSize: '20px', verticalAlign: '0' }" />
        &nbsp; Ваши проекты
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900" v-if="client_rights">
            <h1 class="font-bold">Список проектов</h1>
            <ul class="mt-8">
              <li
                v-for="project in projects"
                :key="project.id"
                class="project-item"
              >
                <!-- Добавляем Tooltip и иконку в зависимости от состояния проекта -->
                <Tooltip
                  :title="
                    project.is_active ? 'Активный проект' : 'Проект неактивен'
                  "
                >
                  <span class="icon-wrapper">
                    <EyeOutlined
                      v-if="project.is_active"
                      class="icon-eye active"
                    />
                    <EyeInvisibleOutlined v-else class="icon-eye inactive" />
                  </span>
                </Tooltip>

                <!-- Динамическая ссылка на проект -->
                <Link
                  :href="`/project/${project.id}`"
                  v-if="project.is_active"
                  >{{ project.name }}</Link
                >
                <Link :href="`/project/${project.id}`" v-else>{{
                  project.name
                }}</Link>
              </li>
            </ul>
          </div>

          <div class="p-6 text-gray-900" v-else>
            У вас заблокированны права доступа. Обратитесь к администратору.
          </div>
        </div>
      </div>
    </div>

    <!--     <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">


            <h2>Карточка клиента: ID клиента -</h2>

            <main
              class="border md:w-3/4 lg:w-4/4 px-5 py-10 mt-5"
              style="padding: 0"
            >
              <div
                class="bg-white my-5 w-full flex flex-col space-y-4 md:flex-row md:space-x-4 md:space-y-0"
              >
                <div class="md:w-2/4 lg:w-2/4 px-5">454</div>
                <div class="md:w-2/4 lg:w-2/4 px-5">46757</div>
              </div>
            </main>
          </div>
        </div>
      </div>
    </div> -->
  </AuthenticatedLayout>
</template>



<style scoped>
/*:deep(.ant-table-content) {
  width: 1200px !important;
}*/
.delete-button:hover {
  background-color: #ff7875; /* Цвет фона при наведении */
  border-color: #ff7875; /* Цвет границы для кнопки редактирования */
}

.custom-rate {
  font-size: 16px; /* Измените размер по вашему желанию */
  /* Вы также можете использовать другие параметры, такие как line-height, color и т.д. */
}

:deep(.text-center) {
  text-align: center;
}

/** Вывод проектов */

/* Контейнер для проекта */
.project-item {
  display: flex; /* Используем flex для горизонтального выравнивания */
  align-items: center; /* Выровнять элементы по вертикали */
  margin-bottom: 1rem; /* Отступ между элементами списка */
}

/* Контейнер для иконки "глаз" */
.icon-wrapper {
  margin-right: 8px; /* Отступ между иконкой и текстом */
  display: inline-flex; /* Центровка иконки */
  align-items: center; /* Выровнять иконку по вертикали */
}

/* Стили для активных и неактивных иконок */
.icon-eye {
  font-size: 16px;
}

.icon-eye.active {
  color: green;
}

.icon-eye.inactive {
  color: gray;
}
</style>

