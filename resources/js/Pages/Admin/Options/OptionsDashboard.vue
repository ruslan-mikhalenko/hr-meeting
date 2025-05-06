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

const props = defineProps({
  rights: {
    type: Boolean,
  },
  user_auth: {
    type: Array,
    required: true,
  },
  users: {
    type: Array,
    required: true,
  },
  pagination: {
    type: Object,
    required: true,
  },
});

/* alert(props.users.role); */
</script>

<template>
  <Head title="Dashboard" />

  <AuthenticatedLayout :user_auth="props.user_auth">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        <SelectOutlined :style="{ fontSize: '20px' }" /> &nbsp; Опции
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div class="flex items-center justify-center">
                <Link
                  :href="route('dashboard_profession')"
                  class="bg-white-500 border text-black font-bold h-16 w-full max-w-xs rounded flex items-center justify-center"
                >
                  Направления
                </Link>
              </div>

              <div class="flex items-center justify-center">
                <Link
                  :href="route('dashboard_unitorder')"
                  class="bg-white-500 border text-black font-bold h-16 w-full max-w-xs rounded flex items-center justify-center"
                >
                  Ед.учёта объёма работ
                </Link>
              </div>

              <div class="flex items-center justify-center">
                <Link
                  :href="route('dashboard_currency')"
                  class="bg-white-500 border text-black font-bold h-16 w-full max-w-xs rounded flex items-center justify-center"
                >
                  Валюта
                </Link>
              </div>
            </div>
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
