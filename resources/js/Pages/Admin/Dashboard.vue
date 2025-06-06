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

const localUsers = ref([...props.users]); // Локальная копия массива пользователей
const pagination = ref({ ...props.pagination }); // Инициализация локальной пагинации
const searchTerm = ref(""); // Переменная для хранения текста поиска
const searchCompletely = ref(); // Для сохранения значения состоаяния поиска при фильтрах
// Здесь мы сохраняем последние значения сортировки
const sortField = ref("created_at"); // Сортировка по умолчанию
const sortOrder = ref("desc"); // Порядок по умолчанию

//Загрузка текущего состояния таблицы Users при изменении пагинации, поиска, сортировки
const fetchUsers = (
  page,
  searchTerm = null,
  sortField = null,
  sortOrder = null
) => {
  isLoading.value = true; // Состояние загрузки при запросе данных
  /*  alert(page); */

  axios
    .post(route("filtering_user"), {
      page: page,
      search: searchTerm,
      sortField: sortField,
      sortOrder: sortOrder,
    })
    .then(function (response) {
      localUsers.value = response.data.users; // Обновляем список пользователей
      pagination.value = {
        current_page: response.data.pagination.current_page,
        per_page: response.data.pagination.per_page,
        total: response.data.pagination.total,
        last_page: response.data.pagination.last_page,
      }; // Обновляем данные пагинации

      // Проверка на случай, если текущая страница пустая
      if (localUsers.value.length === 0 && pagination.value.current_page > 1) {
        // Если текущая страница пустая, уменьшаем её на 1
        pagination.value.current_page--; // Переход на предыдущую страницу

        // Заново загружаем пользователей с новой текущей страницы
        return fetchUsers(
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
  fetchUsers(page, current_search, currentSortField, currentSortOrder);
};

//Метод поиска
const onSearch = () => {
  /* alert(searchTerm.value); */
  // Каждый раз при изменении поля поиска обновляем список пользователей
  /* alert(pagination.value.current_page); */
  searchCompletely.value = searchTerm.value;
  pagination.value.current_page = 1; // Сбрасываем на первую страницу
  fetchUsers(pagination.value.current_page, searchTerm.value); // Передаем новый поиск
};

//Метод сотрировки
const handleTableChange = (current_page, current_search, sorter) => {
  sortField.value = sorter.columnKey;
  sortOrder.value = sorter.order === "ascend" ? "asc" : "desc";
  current_page = pagination.value.current_page;
  current_search = searchCompletely.value;
  /* alert(searchCompletely.value); */
  fetchUsers(
    current_page,
    current_search,
    sorter.columnKey,
    sorter.order === "ascend" ? "asc" : "desc"
  );
};

// Первичная загрузка пользователей при монтировании компонента (если нужно)
onMounted(() => {
  fetchUsers(
    pagination.value.current_page,
    "",
    sortField.value,
    sortOrder.value
  ); // Загружаем данные текущей страницы
});

// Обработчик изменений переключателя Активации участника
const handleActivationChange = (id, checked) => {
  // Здесь можно выполнить дополнительные действия при изменении состояния
  form.isActive[id] = checked; // Обновляем состояние активизации
  /*  alert(form.isActive[id]); */
  axios
    .post(route("axios_active_user", { id: id, checked: checked }))
    .then((response) => {
      /* // Если необходимо обновить вакансию работодателя, когда он становится активным
      const user = response.data.user; // Ответ содержит обновлённую вакансию работодателя
      const updatedUser = {
        ...localUsers.value.find((emp) => emp.id === id),
        ...user,
      }; // Объединяем старые поля и новые

      localUsers.value = localUsers.value.map((emp) =>
        emp.id === id ? updatedUser : emp
      ); */
      updateUsers();
    })
    .catch((error) => {
      errDublMessage.value = "Не удалось активировать участника";
    })
    .finally(() => {
      isLoading.value = false; // Сбрасываем состояние загрузки
      /* isModalDeleted.value = false; */
    });
};

// Метод обновления списка пользователей
const updateUsers = () => {
  fetchUsers(pagination.value.current_page); // Загружаем данные текущей страницы
};

// Наблюдатель для обновления локального массива пользователей при изменении props
/* watch(
  () => props.users,
  (newUsers) => {
    localUsers.value = [...newUsers];
  }
); */

//Исходные колонки вывода в таблице Ant Desing
const columns = ref([
  {
    title: "id",
    dataIndex: "id",
    key: "id",
  },
  {
    title: "Наименование",
    dataIndex: "name",
    key: "name",
    sorter: true,
    /*  width: "300px", */
  },

  {
    title: "Телеграм аккаунт",
    dataIndex: "telegram",
    key: "telegram",
    className: "text-center",
  },

  {
    title: "Дата создания",
    dataIndex: "created_at",
    key: "created_at",
    sorter: true,
  },
  {
    title: "Электронная почта",
    dataIndex: "original_email",
    key: "original_email",
  },
  {
    title: "Пароль входа",
    dataIndex: "original_password",
    key: "original_password",
  },

  {
    title: "Действие",
    key: "action",
  },
  {
    title: "Активация",
    key: "activation",
  },
]);

//Сброс полей формы и вспомогательных выражений при выполенных событиях
const resetForm = () => {
  form.name = "";
  form.telegram = "";
  form.email = "";
  form.phone_number = "";

  form.role = "";
  /* form.password = ""; */
  errors.value = {}; // Очищаем ошибки
  successMessage.value = "";
  errDublMessage.value = ""; // Очищаем сообщение об успехе
};

//Поля формы
const form = useForm({
  name: "",
  telegram: "",
  email: "",
  phone_number: "",
  role: "",
  isActive: {},
  /* password: "", */ // Инициализация поля
});

const errors = ref({});
const successMessage = ref("");
const errDublMessage = ref("");

const editUserId = ref(null); // Для хранения id редактируемого пользователя
const deletUserId = ref(null); // Для хранения id удаляемого пользователя

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
  isModalDetails.value = false;

  successMessage.value = "";
  errDublMessage.value = ""; // Очищаем сообщение об успехе
  resetForm(); // Сбрасываем значения формы также
}

//Вызов модального окна Удаления сущности
function openModalDeleted(id) {
  deletUserId.value = id;
  isModalDeleted.value = true;
}

//Метод добавленяи сущности в БД и отрисовку на странице без перезагрузки
const submitAddForm = () => {
  isLoading.value = true; // Устанавливаем состояние загрузки в true
  errors.value = {}; // Сбрасываем предыдущие ошибки
  successMessage.value = ""; // Сбрасываем предыдущее сообщение успеха
  errDublMessage.value = ""; // Сбрасываем предыдущие сообщения об ошибке

  axios
    .post(route("axios_add_user"), {
      name: form.name,
      telegram: form.telegram, // Отправляем Telegram
      phone_number: form.phone_number, // Отправляем телефон
      email: form.email,
      role: form.role,
    })
    .then((response) => {
      console.log(response.data);
      // Добавляем нового пользователя в локальное состояние
      localUsers.value.unshift(response.data.user);

      successMessage.value =
        "Клиент успешно добавлен! Данные доступа отправлены ему на email.";

      // Обновление данных на странице
      updateUsers();

      const usersPerPage = pagination.value.per_page; // Количество пользователей на странице
      const totalUsersAfterAdd = pagination.value.total + 1;

      // Увеличиваем общее количество пользователей
      pagination.value.total = totalUsersAfterAdd;

      // Проверяем состояние пагинации
      const maxPage = Math.ceil(totalUsersAfterAdd / usersPerPage); // Вычисляем новую последнюю страницу

      if (
        totalUsersAfterAdd % usersPerPage === 1 &&
        pagination.value.last_page < maxPage
      ) {
        pagination.value.last_page = maxPage; // Обновляем значение last_page
      }
    })
    .catch((error) => {
      if (error.response) {
        // Ошибки валидации от сервера
        if (error.response.data.errors) {
          console.error("Ошибки валидации:", error.response.data.errors);
          errors.value = error.response.data.errors;
        } else {
          // Обработка основного сообщения об ошибке
          errDublMessage.value =
            error.response.data.message ||
            "Произошла ошибка при добавлении клиента.";
        }
      } else if (error.request) {
        // Обработка случая, если сервер не отвечает
        console.error("Ошибка с запросом:", error.request);
        errDublMessage.value =
          "Сервер не отвечает. Проверьте соединение с интернетом.";
      } else {
        // Обработка других ошибок
        console.error("Ошибка при отправке данных:", error);
        errDublMessage.value = "Произошла непредвиденная ошибка.";
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
  errors.value = {}; // Сброс предыдущих ошибок

  axios
    .post(route("axios_edit_user"), {
      id: id,
    })
    .then(function (response) {
      const user = response.data.user;

      if (user) {
        form.name = user.name || ""; // Имя пользователя
        form.email = user.email || ""; // Email пользователя
        form.telegram = user.telegram || ""; // Telegram пользователя
        form.phone_number = user.phone_number || ""; // Номер телефона пользователя
        form.role = user.role || ""; // Роль пользователя
        editUserId.value = user.id; // Сохраняем ID пользователя для дальнейшего обновления
        isModalEdit.value = true;
      } else {
        console.error("Данные пользователя не найдены.");
      }
    })
    .catch((error) => {
      if (error.response && error.response.data.errors) {
        errors.value = error.response.data.errors; // Сохраняем ошибки
        console.error("Ошибки валидации:", error.response.data.errors);
      } else if (error.request) {
        console.error("Ошибка с запросом:", error.request);
      } else {
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
  if (!editUserId.value) {
    console.error("ID пользователя не установлен!");
    return;
  }

  isLoading.value = true; // Устанавливаем состояние загрузки в true
  errors.value = {}; // Сбрасываем ошибки
  successMessage.value = ""; // Сбрасываем успешное сообщение

  axios
    .put(route("axios_update_user", { id: editUserId.value }), {
      name: form.name, // Имя
      email: form.email, // Email
      telegram: form.telegram, // Telegram
      phone_number: form.phone_number, // Номер телефона
      role: form.role, // Роль
    })
    .then(function (response) {
      const updatedUser = response.data.user;

      if (updatedUser) {
        successMessage.value = "Клиент успешно обновлен!";

        // Обновление локального состояния пользователей
        localUsers.value = localUsers.value.map((user) =>
          user.id === updatedUser.id ? updatedUser : user
        );

        updateUsers(); // Перезагрузка списка пользователей
      } else {
        console.error("Обновленные данные пользователя не получены.");
      }
    })
    .catch((error) => {
      if (error.response && error.response.data.errors) {
        errors.value = error.response.data.errors; // Сохраняем ошибки
        console.error("Ошибки валидации:", error.response.data.errors);
      } else if (error.request) {
        errDublMessage.value =
          "Позиция с таким email, номером телефона или Telegram уже существует.";
        console.error("Ошибка с запросом:", error.request);
      } else {
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
const deleteUser = () => {
  isLoading.value = true; // Устанавливаем состояние загрузки
  axios
    .delete(route("axios_delete_user", { id: deletUserId.value }))
    .then((response) => {
      // Удаляем пользователя из локального массива
      localUsers.value = localUsers.value.filter(
        (user) => user.id !== deletUserId.value
      );

      updateUsers();

      successMessage.value = "Пользователь успешно удалён!";
    })
    .catch((error) => {
      console.error(
        "Ошибка при удалении пользователя:",
        error.response ? error.response.data : error
      );
      errDublMessage.value = "Не удалось удалить пользователя.";
    })
    .finally(() => {
      isLoading.value = false; // Сбрасываем состояние загрузки
      /* isModalDeleted.value = false; */
    });
};

//Переменная блокировки выводимых id users в таблице
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
    idsToDelete.map((id) => axios.delete(route("axios_delete_user", { id })))
  )
    .then(() => {
      // После успешного удаления обновляем список пользователей
      localUsers.value = localUsers.value.filter(
        (user) => !idsToDelete.includes(user.id)
      );
      selectedRowKeys.value = []; // Очищаем выбранные ключи
      successMessage.value = "Пользователи успешно удалены"; // уведомление об успехе
      fetchUsers(pagination.value.current_page); // Заносим обновленный список пользователей
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

/*----------- Модалка по клику по строке в вытягиваением данных и Скрытие и закрытие доп. полей------- */

// Состояние для видимости колонок
const visibleColumns = ref({
  id: true,
  name: true,
  rating: true,
  unp: true,
  role: true,
  created_at: true,
  original_email: true,
  original_password: true,
  action: true,
  activation: true,
});

// Переменная для отслеживания общей видимости колонок
const areColumnsVisible = ref(false);
// Функция для показа нескольких колонок сразу
// Функция для переключения видимости нескольких колонок
const toggleMultipleColumns = () => {
  areColumnsVisible.value = !areColumnsVisible.value;

  // Устанавливаем состояние видимости колонок
  /*   visibleColumns.value.unp = areColumnsVisible.value; // ИНН
  visibleColumns.value.created_at = areColumnsVisible.value; // Электронная почта
  visibleColumns.value.original_email = areColumnsVisible.value; // Электронная почта
  visibleColumns.value.original_password = areColumnsVisible.value; // Пароль входа */
};

// Фильтрация колонок на основе видимости
const filteredColumns = computed(() => {
  return columns.value.filter((column) => visibleColumns.value[column.key]);
});

const isModalDetails = ref(false);
// Хранение данных выбранной строки
const selectedRow = ref({});

function openModalDetails(record, index) {
  return {
    ondblclick: (event) => {
      // Проверяем, был ли клик по кнопке (или другому интерактивному элементу)
      if (event.target.tagName === "BUTTON" || event.target.closest("button")) {
        event.stopPropagation(); // Останавливаем всплытие события
        return;
      }

      selectedRow.value = record; // Сохраняем данные строки
      isModalDetails.value = true;
    },
  };
}
</script>

<template>
  <Head title="Клиенты" />

  <AuthenticatedLayout :user_auth="props.user_auth">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        <SelectOutlined :style="{ fontSize: '20px', verticalAlign: '0' }" />
        &nbsp; Управление клиентами
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <button
              @click="openModal"
              class="mt-4 bg-gray-700 text-white px-4 py-2 rounded"
            >
              Добавить клиента
            </button>

            <a-input
              placeholder="Введите имя клиента, телефон, email, telegram"
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

            <!-- Кнопки для управления видимостью колонок -->
            <div class="mb-4 flex justify-end mt-6">
              <a-button @click="toggleMultipleColumns">
                {{ areColumnsVisible ? "- Скрыть" : "+ Показать" }} доп.поля
              </a-button>
            </div>

            <a-table
              :row-key="(record) => record.id"
              :data-source="localUsers"
              :columns="filteredColumns"
              :pagination="false"
              :row-selection="rowSelection"
              @change="handleTableChange"
              :customRow="openModalDetails"
              class="w-full overflow-x-auto mt-9 z-0"
            >
              <template #bodyCell="{ column, record }">
                <template v-if="column.key === 'rating'">
                  <a-rate
                    v-model:value="record.rating"
                    @change="(value) => onRateChange(value, record.id_user)"
                    :disabled="true"
                    class="custom-rate"
                  />
                </template>

                <template v-if="column.key === 'name'">
                  <span>{{ record.name }}</span>
                </template>

                <template v-if="column.key === 'unp'">
                  <span>{{ record.unp }}</span>
                  <div v-if="record.parent" class="text-center">
                    {{ record.parent }}
                  </div>
                </template>

                <template v-if="column.key === 'role'">
                  <span v-if="record.role === 'hr'">Кадровик</span>
                  <span v-if="record.role === 'employer'">Работодатель</span>
                </template>

                <template v-if="column.key === 'created_at'">
                  <span>{{ formatDate(record.created_at) }}</span>
                </template>

                <template v-if="column.key === 'activation'">
                  <div v-if="!isExcluded(record.id)">
                    <a-switch
                      v-model="form.isActive[record.id]"
                      @change="
                        (checked) => handleActivationChange(record.id, checked)
                      "
                      :checked="record.is_active === 1"
                    />
                  </div>
                </template>

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
              :isOpen="isModalDetails"
              title="Данные клиента"
              @close="closeModal"
              class="z-10"
            >
              <!-- Предзагрузчик -->
              <div
                v-if="isLoading"
                class="flex justify-center items-center mt-4"
              >
                <Loader />
              </div>

              <!-- Содержимое модального окна -->
              <div class="p-6 space-y-4">
                <div
                  class="flex gap-4 items-center pb-4 border-b border-gray-200"
                >
                  <strong class="text-gray-700">ID:</strong>
                  <span class="text-gray-900">{{ selectedRow.id }}</span>
                </div>

                <div class="flex gap-4 items-center py-2 border-b">
                  <strong class="text-gray-700">Имя:</strong>
                  <span class="text-gray-900">{{ selectedRow.name }}</span>
                </div>

                <div class="flex gap-4 items-center py-2 border-b">
                  <strong class="text-gray-700">Роль:</strong>
                  <span class="text-gray-900">{{ selectedRow.role }}</span>
                </div>

                <div class="flex gap-4 items-center py-2 border-b">
                  <strong class="text-gray-700">Дата создания:</strong>
                  <span class="text-gray-900">{{
                    formatDate(selectedRow.created_at)
                  }}</span>
                </div>
              </div>

              <!-- Нижняя панель с кнопкой -->
              <div
                class="px-6 py-4 bg-gray-50 border-gray-200 flex justify-center"
              >
                <button
                  type="button"
                  class="inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  @click="closeModal"
                >
                  Закрыть
                </button>
              </div>
            </Modal>

            <Modal
              :isOpen="isModalDeleted"
              title="Удаление клиента"
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
              <form @submit.prevent="deleteUser" autocomplete="off">
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
              title="Форма редактирования клиента"
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

                  <!-- Имя -->
                  <div class="mb-4">
                    <label
                      for="name"
                      class="block text-sm font-medium text-gray-700"
                      >Имя:</label
                    >
                    <input
                      type="text"
                      id="name"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder="Наименование клиента"
                      v-model="form.name"
                      :class="{ 'border-red-500': errors.name }"
                    />
                    <span v-if="errors.name" class="text-red-500 err">{{
                      errors.name[0]
                    }}</span>
                  </div>

                  <!-- Телеграм -->
                  <div class="mb-4">
                    <label
                      for="telegram"
                      class="block text-sm font-medium text-gray-700"
                      >Телеграмм аккаунт:</label
                    >
                    <input
                      type="text"
                      id="telegram"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder="Телеграмм аккаунт"
                      v-model="form.telegram"
                      :class="{ 'border-red-500': errors.telegram }"
                    />
                    <span v-if="errors.telegram" class="text-red-500 err">{{
                      errors.telegram[0]
                    }}</span>
                  </div>

                  <!-- Номер телефона -->
                  <div class="mb-4">
                    <label
                      for="phone_number"
                      class="block text-sm font-medium text-gray-700"
                      >Номер телефона:</label
                    >
                    <input
                      type="text"
                      id="phone_number"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder="Номер телефона"
                      v-model="form.phone_number"
                      :class="{ 'border-red-500': errors.phone_number }"
                    />
                    <span v-if="errors.phone_number" class="text-red-500 err">{{
                      errors.phone_number[0]
                    }}</span>
                  </div>

                  <!-- Email -->
                  <div class="mb-4">
                    <label
                      for="email"
                      class="block text-sm font-medium text-gray-700"
                      >Email:</label
                    >
                    <input
                      type="email"
                      id="email"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder="Email"
                      v-model="form.email"
                      :class="{ 'border-red-500': errors.email }"
                    />
                    <span v-if="errors.email" class="text-red-500 err">{{
                      errors.email[0]
                    }}</span>
                  </div>

                  <!-- Роль -->
                  <div class="mb-4">
                    <label
                      for="role"
                      class="block text-sm font-medium text-gray-700"
                      >Роль:</label
                    >
                    <select
                      id="role"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      v-model="form.role"
                      :class="{ 'border-red-500': errors.role }"
                    >
                      <option value="" disabled selected>Выберите роль</option>
                      <option value="client">Клиент</option>
                      <option value="admin">Админ</option>
                    </select>
                    <span v-if="errors.role" class="text-red-500 err">{{
                      errors.role[0]
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
              title="Форма добавления клиента"
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

                  <!-- Имя -->
                  <div class="mb-4">
                    <label
                      for="name"
                      class="block text-sm font-medium text-gray-700"
                      >Имя:</label
                    >
                    <input
                      type="text"
                      id="name"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder="Наименование клиента"
                      v-model="form.name"
                      :class="{ 'border-red-500': errors.name }"
                    />
                    <span v-if="errors.name" class="text-red-500 err">{{
                      errors.name[0]
                    }}</span>
                  </div>

                  <!-- Телеграм -->
                  <div class="mb-4">
                    <label
                      for="telegram"
                      class="block text-sm font-medium text-gray-700"
                      >Телеграмм аккаунт:</label
                    >
                    <input
                      type="text"
                      id="telegram"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder="Телеграмм аккаунт"
                      v-model="form.telegram"
                      :class="{ 'border-red-500': errors.telegram }"
                    />
                    <span v-if="errors.telegram" class="text-red-500 err">{{
                      errors.telegram[0]
                    }}</span>
                  </div>

                  <!-- Номер телефона -->
                  <div class="mb-4">
                    <label
                      for="phone_number"
                      class="block text-sm font-medium text-gray-700"
                      >Номер телефона:</label
                    >
                    <input
                      type="text"
                      id="phone_number"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder="Номер телефона"
                      v-model="form.phone_number"
                      :class="{ 'border-red-500': errors.phone_number }"
                    />
                    <span v-if="errors.phone_number" class="text-red-500 err">{{
                      errors.phone_number[0]
                    }}</span>
                  </div>

                  <!-- Email -->
                  <div class="mb-4">
                    <label
                      for="email"
                      class="block text-sm font-medium text-gray-700"
                      >Email:</label
                    >
                    <input
                      type="email"
                      id="email"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder="Email"
                      v-model="form.email"
                      :class="{ 'border-red-500': errors.email }"
                    />
                    <span v-if="errors.email" class="text-red-500 err">{{
                      errors.email[0]
                    }}</span>
                  </div>

                  <!-- Роль -->
                  <div class="mb-4">
                    <label
                      for="role"
                      class="block text-sm font-medium text-gray-700"
                      >Роль:</label
                    >
                    <select
                      id="role"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      v-model="form.role"
                      :class="{ 'border-red-500': errors.role }"
                    >
                      <option value="" disabled selected>Выберите роль</option>
                      <option value="client">Клиент</option>
                      <option value="admin">Админ</option>
                    </select>
                    <span v-if="errors.role" class="text-red-500 err">{{
                      errors.role[0]
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
</style>
