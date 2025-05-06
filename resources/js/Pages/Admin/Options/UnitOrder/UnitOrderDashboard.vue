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
  unit_orders: {
    type: Array,
    required: true,
  },
  pagination: {
    type: Object,
    required: true,
  },
});

const localUnitOrders = ref([...props.unit_orders]); // Локальная копия массива пользователей
const pagination = ref({ ...props.pagination }); // Инициализация локальной пагинации
const searchTerm = ref(""); // Переменная для хранения текста поиска
const searchCompletely = ref(); // Для сохранения значения состоаяния поиска при фильтрах
// Здесь мы сохраняем последние значения сортировки
const sortField = ref("created_at"); // Сортировка по умолчанию
const sortOrder = ref("desc"); // Порядок по умолчанию

//Загрузка текущего состояния таблицы UnitOrders при изменении пагинации, поиска, сортировки
const fetchUnitOrders = (
  page,
  searchTerm = null,
  sortField = null,
  sortOrder = null
) => {
  isLoading.value = true; // Состояние загрузки при запросе данных
  /*  alert(page); */

  axios
    .post(route("filtering_unit_orders"), {
      page: page,
      search: searchTerm,
      sortField: sortField,
      sortOrder: sortOrder,
    })
    .then(function (response) {
      localUnitOrders.value = response.data.unit_orders; // Обновляем список пользователей
      pagination.value = {
        current_page: response.data.pagination.current_page,
        per_page: response.data.pagination.per_page,
        total: response.data.pagination.total,
        last_page: response.data.pagination.last_page,
      }; // Обновляем данные пагинации

      // Проверка на случай, если текущая страница пустая
      if (
        localUnitOrders.value.length === 0 &&
        pagination.value.current_page > 1
      ) {
        // Если текущая страница пустая, уменьшаем её на 1
        pagination.value.current_page--; // Переход на предыдущую страницу

        // Заново загружаем пользователей с новой текущей страницы
        return fetchUnitOrders(
          pagination.value.current_page,
          searchTerm,
          sortField,
          sortOrder
        );
      }
    })
    .catch((error) => {
      if (error.response && error.response.data.errors) {
        // Выводим в консоль ошибки валидации
        /* console.error("Ошибки валидации:", error.response.data.errors); */
        errors.value = error.response.data.errors; // Сохраняем их в состоянии

        /* alert(error.response.data.message); */
      } else if (error.request) {
        // Запрос был сделан, но не получен ответ

        console.error("Ошибка с запросом:", error.request);
      } else {
        // Обработка других ошибок
        console.error(
          "Ошибка при отправке данных:",
          error.response ? error.response.data : error
        );
      }
    })
    .finally(() => {
      isLoading.value = false; // Сбрасываем состояние загрузки
    });
};

// Пагинация - Инициализация обработки изменения страницы
const handlePageChange = (page, current_search, sorter = {}) => {
  const currentSortField = sortField.value;
  const currentSortOrder = sortOrder.value;
  current_search = searchCompletely.value;

  // Загружаем пользователей с учетом текущей страницы и сортировки
  fetchUnitOrders(page, current_search, currentSortField, currentSortOrder);
};

//Метод поиска
const onSearch = () => {
  /* alert(searchTerm.value); */
  // Каждый раз при изменении поля поиска обновляем список пользователей
  /* alert(pagination.value.current_page); */
  searchCompletely.value = searchTerm.value;
  pagination.value.current_page = 1; // Сбрасываем на первую страницу
  fetchUnitOrders(pagination.value.current_page, searchTerm.value); // Передаем новый поиск
};

//Метод сотрировки
const handleTableChange = (current_page, current_search, sorter) => {
  sortField.value = sorter.columnKey;
  sortOrder.value = sorter.order === "ascend" ? "asc" : "desc";
  current_page = pagination.value.current_page;
  current_search = searchCompletely.value;
  /* alert(searchCompletely.value); */
  fetchUnitOrders(
    current_page,
    current_search,
    sorter.columnKey,
    sorter.order === "ascend" ? "asc" : "desc"
  );
};

// Первичная загрузка пользователей при монтировании компонента (если нужно)
onMounted(() => {
  fetchUnitOrders(
    pagination.value.current_page,
    "",
    sortField.value,
    sortOrder.value
  ); // Загружаем данные текущей страницы
});

// Метод обновления списка пользователей
const updateUnitOrders = () => {
  fetchUnitOrders(pagination.value.current_page); // Загружаем данные текущей страницы
};

// Наблюдатель для обновления локального массива пользователей при изменении props
/* watch(
  () => props.unit_orders,
  (newUnitOrders) => {
    localUnitOrders.value = [...newUnitOrders];
  }
); */

//Исходные колонки вывода в таблице Ant Desing
const columns = ref([
  {
    title: "Наименование",
    dataIndex: "name",
    key: "name",
    sorter: true,
    width: "300px",
  },

  {
    title: "Действие",
    key: "action",
  },
]);

//Сброс полей формы и вспомогательных выражений при выполенных событиях
const resetForm = () => {
  form.name = "";

  /* form.password = ""; */
  errors.value = {}; // Очищаем ошибки
  successMessage.value = "";
  errDublMessage.value = ""; // Очищаем сообщение об успехе
};

//Поля формы
const form = useForm({
  name: "",

  /* password: "", */ // Инициализация поля
});

const errors = ref({});
const successMessage = ref("");
const errDublMessage = ref("");

const editUnitOrderId = ref(null); // Для хранения id редактируемого пользователя
const deletUnitOrderId = ref(null); // Для хранения id удаляемого пользователя

//Модальные окна в положении false - скрыты
const isModalOpen = ref(false);
const isModalEdit = ref(false);
const isModalDeleted = ref(false);
const isLoading = ref(false); // Состояние для предзагрузчика

//Вызов модальнго окна по добавлению сущности
function openModal() {
  resetForm();
  isModalOpen.value = true;
}

//Скрытие модальнго окна
function closeModal() {
  isModalOpen.value = false;
  isModalEdit.value = false;
  isModalDeleted.value = false;

  successMessage.value = "";
  errDublMessage.value = ""; // Очищаем сообщение об успехе
  resetForm(); // Сбрасываем значения формы также
}

//Вызов модального окна Удаления сущности
function openModalDeleted(id) {
  deletUnitOrderId.value = id;
  isModalDeleted.value = true;
}

//Метод добавленяи сущности в БД и отрисовку на странице без перезагрузки
const submitAddForm = () => {
  isLoading.value = true; // Устанавливаем состояние загрузки в true

  axios
    .post(route("axios_add_unit_order"), {
      name: form.name,

      /* password: form.password, */
    })
    .then(function (response) {
      console.log(response.data);
      localUnitOrders.value.unshift(response.data.unit_order); // Добавляем нового пользователя
      successMessage.value = "Учетная единица объема успешно добавлена!";

      /* resetForm(); */ // Очистка формы
      /* closeModal(); */

      updateUnitOrders();
      const unit_ordersPerPage = pagination.value.per_page; // Количество пользователей на странице

      // Проверяем, если мы на последней странице и добавление пользователя увеличивает общее количество
      if (
        pagination.value.current_page === pagination.value.last_page &&
        pagination.value.total >
          (pagination.value.last_page - 1) * unit_ordersPerPage
      ) {
        // Увеличиваем номер последней страницы, если добавили нового пользователя
        pagination.value.last_page++;
      }

      // Проверяем, есть ли пользователи на текущей странице
      if (localUnitOrders.value.length === 0) {
        // Если текущая страница пустая, переходим на первую страницу
        pagination.value.current_page = 1;
        fetchUnitOrders(pagination.value.current_page); // Загружаем пользователей с первой страницы
      }
    })
    .catch((error) => {
      if (error.response && error.response.data.errors) {
        // Выводим в консоль ошибки валидации
        /* console.error("Ошибки валидации:", error.response.data.errors); */
        errors.value = error.response.data.errors; // Сохраняем их в состоянии

        /* alert(error.response.data.message); */
      } else if (error.request) {
        // Запрос был сделан, но не получен ответ
        errDublMessage.value = "Позиция с таким email или ИНН уже существует";

        console.error("Ошибка с запросом:", error.request);
      } else {
        // Обработка других ошибок
        console.error(
          "Ошибка при отправке данных:",
          error.response ? error.response.data : error
        );
      }
    })
    .finally(() => {
      isLoading.value = false; // Сбрасываем состояние загрузки
    });
};

//Функционал по вызову модальных окон и их обработке
//Вызов модального окна Редактирования сущности
function openModalEdit(id) {
  isLoading.value = true; // Устанавливаем состояние загрузки в true

  axios
    .post(route("axios_edit_unit_order"), {
      id: id,
    })
    .then(function (response) {
      form.name = response.data.unit_order.name;

      editUnitOrderId.value = response.data.unit_order.id; // Сохраняем id для обновления
      isModalEdit.value = true;
    })
    .catch((error) => {
      if (error.response && error.response.data.errors) {
        // Выводим в консоль ошибки валидации
        /* console.error("Ошибки валидации:", error.response.data.errors); */
        errors.value = error.response.data.errors; // Сохраняем их в состоянии

        /* alert(error.response.data.message); */
      } else if (error.request) {
        // Запрос был сделан, но не получен ответ

        console.error("Ошибка с запросом:", error.request);
      } else {
        // Обработка других ошибок
        console.error(
          "Ошибка при отправке данных:",
          error.response ? error.response.data : error
        );
      }
    })
    .finally(() => {
      isLoading.value = false; // Сбрасываем состояние загрузки
    });
}

//Функция по обновлению текущих значений сущности
const submitEditForm = () => {
  isLoading.value = true; // Устанавливаем состояние загрузки в true

  if (!editUnitOrderId.value) {
    console.error("ID Учетной единицы объема не установлен!");
    return;
  }

  axios
    .put(route("axios_update_unit_order", { id: editUnitOrderId.value }), {
      name: form.name,
      /* password: form.password, */
    })
    .then(function (response) {
      successMessage.value = "Учетная единица объема успешно обновлёна!";

      // Обновление существующего пользователя
      const updatedUnitOrder = response.data.unit_order; // Это ваш обновленный объект
      localUnitOrders.value = localUnitOrders.value.map((unit_order) =>
        unit_order.id === updatedUnitOrder.id ? updatedUnitOrder : unit_order
      );
      updateUnitOrders();
      /* resetForm(); */ // Очистка формы
      /* closeModal(); */
    })
    .catch((error) => {
      if (error.response && error.response.data.errors) {
        // Выводим в консоль ошибки валидации
        /* console.error("Ошибки валидации:", error.response.data.errors); */
        errors.value = error.response.data.errors; // Сохраняем их в состоянии

        /* alert(error.response.data.message); */
      } else if (error.request) {
        // Запрос был сделан, но не получен ответ
        errDublMessage.value = "Позиция с таким email или ИНН уже существует";

        console.error("Ошибка с запросом:", error.request);
      } else {
        // Обработка других ошибок
        console.error(
          "Ошибка при отправке данных:",
          error.response ? error.response.data : error
        );
      }
    })
    .finally(() => {
      isLoading.value = false; // Сбрасываем состояние загрузки
    });
};

//Функция по удалению сущности
const deleteUnitOrder = () => {
  isLoading.value = true; // Устанавливаем состояние загрузки
  axios
    .delete(route("axios_delete_unit_order", { id: deletUnitOrderId.value }))
    .then((response) => {
      // Удаляем пользователя из локального массива
      localUnitOrders.value = localUnitOrders.value.filter(
        (unit_order) => unit_order.id !== deletUnitOrderId.value
      );

      updateUnitOrders();

      successMessage.value = "Учетная единица объема успешно удалёна!";
    })
    .catch((error) => {
      console.error(
        "Ошибка при удалении Учетной единицы объема :",
        error.response ? error.response.data : error
      );
      errDublMessage.value = "Не удалось удалить Учетную единицу объема .";
    })
    .finally(() => {
      isLoading.value = false; // Сбрасываем состояние загрузки
      /* isModalDeleted.value = false; */
    });
};

//Переменная блокировки выводимых id unit_orders в таблице
const disallowedIds = []; // Массив запрещённых ID

//Функционал вылеление гурппы строк  - check
const selectedRowKeys = ref([]);

const rowSelection = {
  selectedRowKeys: computed(() => selectedRowKeys.value),

  onChange: (newSelectedRowKeys) => {
    // Фильтруем от новых ключей, чтобы исключить запрещенные ID
    const filteredRowKeys = newSelectedRowKeys.filter(
      (key) => !disallowedIds.includes(key)
    );
    selectedRowKeys.value = filteredRowKeys; // Обновляем выбранные ключи
  },
};

//Вспомогательная функция для запрета вывода определённых строк в таблице
const isExcluded = (id) => {
  return disallowedIds.includes(id);
};

//Метод удаление группы выделенных строк
const deleteSelected = () => {
  const idsToDelete = selectedRowKeys.value;

  if (idsToDelete.length === 0) {
    return; // Если нет выделенных пользователей, ничего не делаем
  }

  isLoading.value = true; // Начинаем загрузку

  // Используем route для создания полноценных URL для удаления
  Promise.all(
    idsToDelete.map((id) =>
      axios.delete(route("axios_delete_unit_order", { id }))
    )
  )
    .then(() => {
      // После успешного удаления обновляем список пользователей
      localUnitOrders.value = localUnitOrders.value.filter(
        (unit_order) => !idsToDelete.includes(unit_order.id)
      );
      selectedRowKeys.value = []; // Очищаем выбранные ключи
      successMessage.value = "Учетные единицы объема успешно удалены"; // уведомление об успехе
      fetchUnitOrders(pagination.value.current_page); // Заносим обновленный список пользователей
    })
    .catch((error) => {
      handleError(error); // Обработка ошибки
    })
    .finally(() => {
      isLoading.value = false; // Сбрасываем состояние загрузки
    });
};

//Переменна для вывода формата даты
const formatDate = (dateString) => {
  const options = {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    hour12: false, // 24-часовой формат времени
  };
  return new Date(dateString).toLocaleDateString("ru-RU", options);
};
</script>

<template>
  <Head title="Dashboard" />

  <AuthenticatedLayout :user_auth="props.user_auth">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-4">
        <SelectOutlined :style="{ fontSize: '20px' }" />
        &nbsp; Ед.учёта работы
      </h2>
      <div>
        <Link :href="route('options')" class="go_back">
          <RollbackOutlined :style="{ fontSize: '16px', color: 'black' }" />
          <span style="margin-left: 8px">Назад</span>
        </Link>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <button
              @click="openModal"
              class="mt-4 bg-gray-700 text-white px-4 py-2 rounded"
            >
              Добавить Учетную единицу объема
            </button>

            <a-input
              placeholder="Введите название УЕО для поиска"
              v-model:value="searchTerm"
              @input="onSearch"
              style="margin-bottom: 16px"
              class="rounded mt-5"
            />

            <a-button
              type="primary"
              @click="deleteSelected"
              :disabled="!selectedRowKeys.length"
            >
              Удалить выделенные
            </a-button>

            <a-table
              :row-key="(record) => record.id"
              :data-source="localUnitOrders"
              :columns="columns"
              :pagination="false"
              :row-selection="rowSelection"
              @change="handleTableChange"
              class="w-full overflow-x-auto mt-9 z-0"
            >
              <template #headerCell="{ column }">
                <template v-if="column.key === 'name'">
                  <span> Наименование ед.учёта </span>
                </template>
              </template>

              <template #bodyCell="{ column, record }">
                <template v-if="column.key === 'action'">
                  <div v-if="!isExcluded(record.id)" class="button-container">
                    <a-button
                      class="edit-button icon-square"
                      @click="openModalEdit(record.id)"
                      shape="square"
                    >
                      <EditOutlined style="color: white" />
                    </a-button>

                    <a-button
                      class="delete-button icon-square"
                      @click="openModalDeleted(record.id)"
                      shape="square"
                    >
                      <DeleteOutlined style="color: white" />
                    </a-button>
                  </div>
                </template>
              </template>
            </a-table>

            <a-pagination
              :current="pagination.current_page"
              :pageSize="pagination.per_page"
              :total="pagination.total"
              @change="handlePageChange"
              style="margin-top: 20px"
            />

            <Modal
              :isOpen="isModalDeleted"
              title="Удаление Учетной единица объема "
              @close="closeModal"
              class="z-10"
            >
              <div v-if="isLoading">
                <Loader />
                <!-- Предзагрузчик -->
              </div>
              <div v-if="successMessage">
                <p class="text-green-500 text-center">{{ successMessage }}</p>
              </div>
              <form @submit.prevent="deleteUnitOrder" autocomplete="off">
                <div class="flex mt-10">
                  <button
                    v-if="!successMessage"
                    type="submit"
                    class="flex-grow inline-flex justify-center py-2 mx-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-700 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    :disabled="isLoading"
                  >
                    Подтвердить
                  </button>
                  <button
                    type="button"
                    class="flex-grow inline-flex justify-center py-2 mx-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    @click="closeModal"
                    :class="{ 'm-auto': successMessage }"
                  >
                    Закрыть
                  </button>
                </div>
              </form>
            </Modal>

            <Modal
              :isOpen="isModalEdit"
              title="Форма редактирования Учетной единицы объема "
              @close="closeModal"
              class="z-10"
            >
              <div v-if="isLoading">
                <Loader />
                <!-- Предзагрузчик -->
              </div>

              <div v-if="successMessage">
                <p class="text-green-500 text-center">{{ successMessage }}</p>
              </div>
              <div v-else>
                <form @submit.prevent="submitEditForm" autocomplete="off">
                  <div v-if="errDublMessage">
                    <p class="text-red-500 text-center">{{ errDublMessage }}</p>
                  </div>
                  <div class="mb-4">
                    <label
                      for="name"
                      class="block text-sm font-medium text-gray-700"
                      >Единица учёта:</label
                    >
                    <input
                      type="text"
                      id="name"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder="Введите наименование Учетной единицы объема "
                      v-model="form.name"
                      :class="{ 'border-red-500': errors.name }"
                    />
                    <span v-if="errors.name" class="text-red-500 err">{{
                      errors.name[0]
                    }}</span>
                  </div>

                  <div class="flex mt-10">
                    <button
                      type="submit"
                      class="flex-grow inline-flex justify-center py-2 mx-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-700 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                      :disabled="isLoading"
                    >
                      Сохранить
                    </button>
                    <button
                      type="button"
                      class="flex-grow inline-flex justify-center py-2 mx-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                      @click="closeModal"
                    >
                      Закрыть
                    </button>
                  </div>
                </form>
              </div>
              <div v-if="successMessage" class="flex justify-center">
                <button
                  type="button"
                  class="flex-grow inline-flex justify-center py-2 mx-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  @click="closeModal"
                >
                  Закрыть
                </button>
              </div>
            </Modal>

            <Modal
              :isOpen="isModalOpen"
              title="Форма добавления Учетной единицы объема "
              @close="closeModal"
              class="z-10"
            >
              <div v-if="isLoading">
                <Loader />
                <!-- Предзагрузчик -->
              </div>

              <div v-if="successMessage">
                <p class="text-green-500 text-center">{{ successMessage }}</p>
              </div>
              <div v-else>
                <form @submit.prevent="submitAddForm" autocomplete="off">
                  <div v-if="errDublMessage">
                    <p class="text-red-500 text-center">{{ errDublMessage }}</p>
                  </div>
                  <div class="mb-4">
                    <label
                      for="name"
                      class="block text-sm font-medium text-gray-700"
                      >Учетная единица объема :</label
                    >
                    <input
                      type="text"
                      id="name"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder="Введите наименование Учетной единицы объема "
                      v-model="form.name"
                      :class="{ 'border-red-500': errors.name }"
                    />
                    <span v-if="errors.name" class="text-red-500 err">{{
                      errors.name[0]
                    }}</span>
                  </div>

                  <div class="flex mt-10">
                    <button
                      type="submit"
                      class="flex-grow inline-flex justify-center py-2 mx-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-700 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                      :disabled="isLoading"
                    >
                      Сохранить
                    </button>
                    <button
                      type="button"
                      class="flex-grow inline-flex justify-center py-2 mx-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                      @click="closeModal"
                    >
                      Закрыть
                    </button>
                  </div>
                </form>
              </div>

              <div v-if="successMessage" class="flex">
                <button
                  type="button"
                  class="flex-grow inline-flex justify-center py-2 mx-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  @click="closeModal"
                >
                  Закрыть
                </button>
              </div>
            </Modal>
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
