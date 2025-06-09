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
  landing_auth: {
    type: Array,
    required: true,
  },
  landings: {
    type: Array,
    required: true,
  },
  pagination: {
    type: Object,
    required: true,
  },
});

const localLandings = ref([...props.landings]); // Локальная копия массива пользователей
const pagination = ref({ ...props.pagination }); // Инициализация локальной пагинации
const searchTerm = ref(""); // Переменная для хранения текста поиска
const searchCompletely = ref(); // Для сохранения значения состоаяния поиска при фильтрах
// Здесь мы сохраняем последние значения сортировки
const sortField = ref("created_at"); // Сортировка по умолчанию
const sortOrder = ref("desc"); // Порядок по умолчанию

//Загрузка текущего состояния таблицы Landings при изменении пагинации, поиска, сортировки
const fetchLandings = (
  page,
  searchTerm = null,
  sortField = null,
  sortOrder = null
) => {
  isLoading.value = true; // Состояние загрузки при запросе данных
  /*  alert(page); */

  axios
    .post(route("filtering_landing"), {
      page: page,
      search: searchTerm,
      sortField: sortField,
      sortOrder: sortOrder,
    })
    .then(function (response) {
      localLandings.value = response.data.landings; // Обновляем список пользователей
      pagination.value = {
        current_page: response.data.pagination.current_page,
        per_page: response.data.pagination.per_page,
        total: response.data.pagination.total,
        last_page: response.data.pagination.last_page,
      }; // Обновляем данные пагинации

      // Проверка на случай, если текущая страница пустая
      if (
        localLandings.value.length === 0 &&
        pagination.value.current_page > 1
      ) {
        // Если текущая страница пустая, уменьшаем её на 1
        pagination.value.current_page--; // Переход на предыдущую страницу

        // Заново загружаем пользователей с новой текущей страницы
        return fetchLandings(
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
  fetchLandings(page, current_search, currentSortField, currentSortOrder);
};

//Метод поиска
const onSearch = () => {
  /* alert(searchTerm.value); */
  // Каждый раз при изменении поля поиска обновляем список пользователей
  /* alert(pagination.value.current_page); */
  searchCompletely.value = searchTerm.value;
  pagination.value.current_page = 1; // Сбрасываем на первую страницу
  fetchLandings(pagination.value.current_page, searchTerm.value); // Передаем новый поиск
};

//Метод сотрировки
const handleTableChange = (current_page, current_search, sorter) => {
  sortField.value = sorter.columnKey;
  sortOrder.value = sorter.order === "ascend" ? "asc" : "desc";
  current_page = pagination.value.current_page;
  current_search = searchCompletely.value;
  /* alert(searchCompletely.value); */
  fetchLandings(
    current_page,
    current_search,
    sorter.columnKey,
    sorter.order === "ascend" ? "asc" : "desc"
  );
};

// Первичная загрузка пользователей при монтировании компонента (если нужно)
onMounted(() => {
  fetchLandings(
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
    .post(route("axios_active_landing", { id: id, checked: checked }))
    .then((response) => {
      /* // Если необходимо обновить вакансию работодателя, когда он становится активным
      const landing = response.data.landing; // Ответ содержит обновлённую вакансию работодателя
      const updatedLanding = {
        ...localLandings.value.find((emp) => emp.id === id),
        ...landing,
      }; // Объединяем старые поля и новые

      localLandings.value = localLandings.value.map((emp) =>
        emp.id === id ? updatedLanding : emp
      ); */
      updateLandings();
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
const updateLandings = () => {
  fetchLandings(pagination.value.current_page); // Загружаем данные текущей страницы
};

// Наблюдатель для обновления локального массива пользователей при изменении props
/* watch(
  () => props.landings,
  (newLandings) => {
    localLandings.value = [...newLandings];
  }
); */

//Исходные колонки вывода в таблице Ant Desing
// Колонки для таблицы Ant Design
const columns = ref([
  {
    title: "ID",
    dataIndex: "id",
    key: "id",
  },

  {
    title: "Проект",
    dataIndex: "project_name",
    key: "project_name",
    sorter: true,
    /* width: "300px", */
  },
  {
    title: "Название лендинга",
    dataIndex: "name",
    key: "name",
    sorter: true,
    /* width: "300px", */
  },
  {
    title: "Ссылка на лендинг",
    dataIndex: "url",
    key: "url",
    className: "text-center",
  },
  {
    title: "Дата создания",
    dataIndex: "created_at",
    key: "created_at",
    sorter: true,
  },

  {
    title: "Действие",
    key: "action",
    // Настройте рендеринг столбцов действия
  },

  {
    title: "Активация",
    key: "activation",
  },
]);

// Поля формы
const form = useForm({
  project_id: null, // ID выбранного проекта (для отправки)
  project: "", // Название проекта (для отображения)
  name: "", // Название лендинга
  url: "", // URL лендинга
  short_description: "", // Краткое описание
  description: "", // Полное описание
  goal_click_id: "",
  goal_subscribe_id: "",
  isActive: {},
});

// Сброс полей формы
const resetForm = () => {
  form.project_id = null;
  form.project = "";
  form.name = "";
  form.url = "";
  form.short_description = "";
  form.description = "";
  form.goal_click_id = "";
  form.goal_subscribe_id = "";
  errors.value = {}; // Очищаем ошибки
  successMessage.value = ""; // Очищаем сообщение об успехе
  errDublMessage.value = ""; // Очищаем сообщение о дубликате
};

const errors = ref({});
const successMessage = ref("");
const errDublMessage = ref("");

const editLandingId = ref(null); // Для хранения id редактируемого пользователя
const deletLandingId = ref(null); // Для хранения id удаляемого пользователя

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
  deletLandingId.value = id;
  isModalDeleted.value = true;
}

//Метод добавленяи сущности в БД и отрисовку на странице без перезагрузки
const submitAddForm = () => {
  isLoading.value = true;
  errors.value = {};
  successMessage.value = "";
  errDublMessage.value = "";

  axios
    .post(route("axios_add_landing"), {
      project_id: form.project_id, // ID выбранного проекта
      name: form.name, // Название лендинга
      url: form.url, // URL лендинга
      short_description: form.short_description, // Краткое описание
      description: form.description, // Полное описание
      goal_click_id: form.goal_click_id,
      goal_subscribe_id: form.goal_subscribe_id,
    })
    .then((response) => {
      console.log(response.data);
      localLandings.value.unshift(response.data.landing);
      successMessage.value = "Лендинг успешно добавлен!";
      updateLandings();

      const landingsPerPage = pagination.value.per_page;
      const totalLandingsAfterAdd = pagination.value.total + 1;
      pagination.value.total = totalLandingsAfterAdd;

      const maxPage = Math.ceil(totalLandingsAfterAdd / landingsPerPage);
      if (
        totalLandingsAfterAdd % landingsPerPage === 1 &&
        pagination.value.last_page < maxPage
      ) {
        pagination.value.last_page = maxPage;
      }
    })
    .catch((error) => {
      if (error.response) {
        if (error.response.data.errors) {
          errors.value = error.response.data.errors;
        } else {
          errDublMessage.value =
            error.response.data.message || "Ошибка при добавлении лендинга.";
        }
      } else if (error.request) {
        errDublMessage.value =
          "Сервер не отвечает. Проверьте соединение с интернетом.";
      } else {
        errDublMessage.value = "Произошла непредвиденная ошибка.";
      }
    })
    .finally(() => {
      isLoading.value = false;
    });
};

//Функционал по вызову модальных окон и их обработке
//Вызов модального окна Редактирования сущности
function openModalEdit(id) {
  isLoading.value = true; // Устанавливаем состояние загрузки в true
  errors.value = {}; // Сброс предыдущих ошибок

  axios
    .post(route("axios_edit_landing"), { id: id })
    .then((response) => {
      const landing = response.data.landing;

      if (landing) {
        form.project_id = landing.project_id || null;
        form.project = landing.project || ""; // если приходит объект проекта
        form.name = landing.name || "";
        form.url = landing.url || "";
        form.short_description = landing.short_description || "";
        form.description = landing.description || "";

        form.goal_click_id = landing.goal_click_id || "";
        form.goal_subscribe_id = landing.goal_subscribe_id || "";

        editLandingId.value = landing.id; // Сохраняем ID проекта для дальнейшего обновления
        isModalEdit.value = true; // Открываем модальное окно
      } else {
        console.error("Лендинг не найден.");
      }
    })
    .catch((error) => {
      if (error.response && error.response.data.errors) {
        errors.value = error.response.data.errors;
        console.error("Ошибки валидации:", error.response.data.errors);
      } else if (error.request) {
        console.error("Ошибка с запросом:", error.request);
      } else {
        console.error("Ошибка при отправке данных:", error);
      }
    })
    .finally(() => {
      isLoading.value = false; // Сбрасываем состояние загрузки
    });
}

//Функция по обновлению текущих значений сущности
const submitEditForm = () => {
  if (!editLandingId.value) {
    console.error("ID лендинг не установлен!");
    return;
  }

  isLoading.value = true; // Устанавливаем состояние загрузки в true
  errors.value = {}; // Сбрасываем ошибки
  successMessage.value = ""; // Сбрасываем успешное сообщение

  axios
    .put(route("axios_update_landing", { id: editLandingId.value }), {
      project_id: form.project_id,
      name: form.name,
      url: form.url,
      short_description: form.short_description,
      description: form.description,
      goal_click_id: form.goal_click_id,
      goal_subscribe_id: form.goal_subscribe_id,
    })
    .then((response) => {
      const updatedLanding = response.data.landing;

      if (updatedLanding) {
        successMessage.value = "Лендинг успешно обновлен!";

        // Обновляем локальное состояние проектов
        localLandings.value = localLandings.value.map((landing) =>
          landing.id === updatedLanding.id ? updatedLanding : landing
        );

        updateLandings(); // Перезагрузка списка проектов
      } else {
        console.error("Обновленные данные лендинга не получены.");
      }
    })
    .catch((error) => {
      if (error.response && error.response.data.errors) {
        errors.value = error.response.data.errors; // Сохраняем ошибки
        console.error("Ошибки валидации:", error.response.data.errors);
      } else if (error.request) {
        errDublMessage.value = "Лендинг с таким названием уже существует.";
        console.error("Ошибка с запросом:", error.request);
      } else {
        console.error("Ошибка при отправке данных:", error);
      }
    })
    .finally(() => {
      isLoading.value = false; // Сбрасываем состояние загрузки
    });
};

//Функция по удалению сущности
const deleteLanding = () => {
  isLoading.value = true; // Устанавливаем состояние загрузки
  axios
    .delete(route("axios_delete_landing", { id: deletLandingId.value }))
    .then((response) => {
      // Удаляем пользователя из локального массива
      localLandings.value = localLandings.value.filter(
        (landing) => landing.id !== deletLandingId.value
      );

      updateLandings();

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

//Переменная блокировки выводимых id landings в таблице
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
    idsToDelete.map((id) => axios.delete(route("axios_delete_landing", { id })))
  )
    .then(() => {
      // После успешного удаления обновляем список пользователей
      localLandings.value = localLandings.value.filter(
        (landing) => !idsToDelete.includes(landing.id)
      );
      selectedRowKeys.value = []; // Очищаем выбранные ключи
      successMessage.value = "Пользователи успешно удалены"; // уведомление об успехе
      fetchLandings(pagination.value.current_page); // Заносим обновленный список пользователей
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
// Состояние для видимости колонок
const visibleColumns = ref({
  project_name: true, // ID проекта
  name: true,
  url: true, // Название проекта
  short_description: true, // Ссылка на проект
  description: true, // ID Яндекс Метрики
  goal_click_id: true, // Идентификатор цели
  goal_subscribe_id: true, // URL лендинга
  created_at: true, // Дата создания
  action: true, // Колонка с действиями
  activation: true,
});

// Переменная для отслеживания общей видимости колонок
const areColumnsVisible = ref(false); // Изначально - показывать только минимальный набор колонок

// Функция для переключения видимости нескольких колонок
const toggleMultipleColumns = () => {
  areColumnsVisible.value = !areColumnsVisible.value;

  // Устанавливаем состояние видимости колонок
  // Если переключаем на отображение всех колонок
  if (areColumnsVisible.value) {
    visibleColumns.value.project_name = true;
    visibleColumns.value.name = true;
    visibleColumns.value.url = true;
    visibleColumns.value.short_description = true;
    visibleColumns.value.description = true;
  } else {
    // Если переключаем на отображение минимального набора
    visibleColumns.value.project_name = true;
    visibleColumns.value.name = true;
    visibleColumns.value.url = true;
    visibleColumns.value.short_description = true;
    visibleColumns.value.description = true;
  }
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

/*  */

const searchProject = ref("");
const resultsProject = ref([]); // Храним все результаты поиска
const selectedIdProject = ref(null);

let issetMediaFile = false;

// Фильтруем результаты для отображения при вводе
const filteredResultsProject = computed(() => {
  return resultsProject.value.filter((item) => {
    const searchTerm = searchProject.value.toLowerCase();

    // Поиск по id (сравнение строчных и числовых значений)
    const idMatches = item.user_id.toString().includes(searchTerm); // id может быть числом
    const firstNameMatches = item.name.toLowerCase().includes(searchTerm);
    /* const lastNameMatches = item.last_name.toLowerCase().includes(searchTerm); */

    // Возвращаем true, если совпадение найдено в любом из полей
    return idMatches || firstNameMatches;
  });
});

/* Выводим список в select по совпадению */
const onSearchProject = async () => {
  if (searchProject.value.length === 0) {
    resultsProject.value = []; // Если поле поиска пустое, очищаем результаты
    return;
  }
  /* alert(searchProject.value); */

  try {
    const response = await axios.get("/projects_search", {
      params: {
        query: searchProject.value,
      },
    });
    resultsProject.value = response.data; // Обновляем результаты поиска
  } catch (error) {
    console.error("Error while searching:", error);
    resultsProject.value = []; // Очищаем результаты в случае ошибки
  }
};

/* Выбранный элемент устанавливаем в поле */
const selectItemProject = (item) => {
  selectedIdProject.value = item.id; // Устанавливаем ID выбранного элемента
  searchProject.value = item.name; // Устанавливаем значение поискового поля на имя элемента
  resultsProject.value = []; // Очищаем список результатов
  console.log("Selected ID:", selectedIdProject.value);

  issetMediaFile = true;

  // Очистите список результатов после выбора
  filteredResultsProject.value = [];
  searchProject.value = ""; // Очищаем строку поиска

  form.project = item.name;
  form.project_id = item.id; // Передача ID
};
</script>

<template>
  <Head title="Лендинги" />

  <AuthenticatedLayout :landing_auth="props.landing_auth">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        <SelectOutlined :style="{ fontSize: '20px', verticalAlign: '0' }" />
        &nbsp; Управление лендингами
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
              Добавить лендинг
            </button>

            <a-input
              placeholder="Введите название лендинга"
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
              :data-source="localLandings"
              :columns="filteredColumns"
              :pagination="false"
              :row-selection="rowSelection"
              @change="handleTableChange"
              :customRow="openModalDetails"
              class="w-full overflow-x-auto mt-9 z-0"
            >
              <template #bodyCell="{ column, record }">
                <template v-if="column.key === 'name'">
                  <span>{{ record.name }}</span>
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
              title="Данные лендинга"
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
              title="Удаление лендинга"
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
              <form @submit.prevent="deleteLanding" autocomplete="off">
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
              title="Форма редактирования лендинга"
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
                  <!-- Проект -->
                  <div class="mb-4">
                    <input
                      type="text"
                      v-model="searchProject"
                      @input="onSearchProject"
                      placeholder="Поиск проекта по id или Названию ..."
                      class="bg-yellow-100 border p-2 w-[100%] rounded-md"
                    />
                    <div
                      v-if="filteredResultsProject.length > 0"
                      class="border mt-2 max-h-60 overflow-auto"
                    >
                      <ul>
                        <li
                          v-for="item in filteredResultsProject"
                          :key="item.id"
                          @click="selectItemProject(item)"
                          class="cursor-pointer p-2 hover:bg-gray-200"
                        >
                          {{ item.name }}
                        </li>
                      </ul>
                    </div>

                    <span v-if="errors.recipient_id" class="text-red-500">{{
                      errors.recipient_id[0]
                    }}</span>
                  </div>

                  <div class="mb-4">
                    <label
                      for="project"
                      class="block text-sm font-medium text-gray-700"
                    >
                      Проект:</label
                    >
                    <input
                      type="text"
                      id="project"
                      class="bg-gray-100 mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder=""
                      v-model="form.project"
                      :class="{ 'border-red-500': errors.project }"
                      readonly
                    />
                    <span v-if="errors.project" class="text-red-500 err">{{
                      errors.project[0]
                    }}</span>
                  </div>

                  <!-- Название лендинга -->
                  <div class="mb-4">
                    <label
                      for="name"
                      class="block text-sm font-medium text-gray-700"
                    >
                      Название лендинга:
                    </label>
                    <input
                      type="text"
                      id="name"
                      v-model="form.name"
                      placeholder="Введите название лендинга"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm"
                      :class="{ 'border-red-500': errors.name }"
                    />
                    <span v-if="errors.name" class="text-red-500">{{
                      errors.name[0]
                    }}</span>
                  </div>

                  <!-- URL лендинга -->
                  <div class="mb-4">
                    <label
                      for="url"
                      class="block text-sm font-medium text-gray-700"
                    >
                      URL лендинга:
                    </label>
                    <input
                      type="text"
                      id="url"
                      v-model="form.url"
                      placeholder="https://example.com"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm"
                      :class="{ 'border-red-500': errors.url }"
                    />
                    <span v-if="errors.url" class="text-red-500">{{
                      errors.url[0]
                    }}</span>
                  </div>

                  <!-- Краткое описание -->
                  <div class="mb-4">
                    <label
                      for="short_description"
                      class="block text-sm font-medium text-gray-700"
                    >
                      Краткое описание:
                    </label>
                    <input
                      type="text"
                      id="short_description"
                      v-model="form.short_description"
                      placeholder="Кратко о лендинге"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm"
                      :class="{ 'border-red-500': errors.short_description }"
                    />
                    <span v-if="errors.short_description" class="text-red-500">
                      {{ errors.short_description[0] }}
                    </span>
                  </div>

                  <!-- Описание -->
                  <div class="mb-4">
                    <label
                      for="description"
                      class="block text-sm font-medium text-gray-700"
                    >
                      Полное описание:
                    </label>
                    <textarea
                      id="description"
                      v-model="form.description"
                      rows="4"
                      placeholder="Полное описание лендинга"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm"
                      :class="{ 'border-red-500': errors.description }"
                    ></textarea>
                    <span v-if="errors.description" class="text-red-500">
                      {{ errors.description[0] }}
                    </span>
                  </div>

                  <!-- ID цели на переход с лендинга -->
                  <div class="mb-4">
                    <label
                      for="goal_click_id"
                      class="block text-sm font-medium text-gray-700"
                    >
                      ID цели (переход с лендинга):
                    </label>
                    <input
                      type="text"
                      id="goal_click_id"
                      v-model="form.goal_click_id"
                      placeholder="Например, 12345678"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm"
                      :class="{ 'border-red-500': errors.goal_click_id }"
                    />
                    <span v-if="errors.goal_click_id" class="text-red-500">
                      {{ errors.goal_click_id[0] }}
                    </span>
                  </div>

                  <!-- ID цели на подписку с лендинга -->
                  <div class="mb-4">
                    <label
                      for="goal_subscribe_id"
                      class="block text-sm font-medium text-gray-700"
                    >
                      ID цели (подписка с лендинга):
                    </label>
                    <input
                      type="text"
                      id="goal_subscribe_id"
                      v-model="form.goal_subscribe_id"
                      placeholder="Например, 87654321"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm"
                      :class="{ 'border-red-500': errors.goal_subscribe_id }"
                    />
                    <span v-if="errors.goal_subscribe_id" class="text-red-500">
                      {{ errors.goal_subscribe_id[0] }}
                    </span>
                  </div>

                  <div class="flex mt-6">
                    <button
                      type="submit"
                      class="flex-grow inline-flex justify-center py-2 mx-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-700 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                      :disabled="isLoading"
                    >
                      Добавить
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
            </Modal>

            <Modal
              :isOpen="isModalOpen"
              title="Форма добавления лэндинга"
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
                  <!-- Проект -->
                  <div class="mb-4">
                    <input
                      type="text"
                      v-model="searchProject"
                      @input="onSearchProject"
                      placeholder="Поиск проекта по id или Названию ..."
                      class="bg-yellow-100 border p-2 w-[100%] rounded-md"
                    />
                    <div
                      v-if="filteredResultsProject.length > 0"
                      class="border mt-2 max-h-60 overflow-auto"
                    >
                      <ul>
                        <li
                          v-for="item in filteredResultsProject"
                          :key="item.id"
                          @click="selectItemProject(item)"
                          class="cursor-pointer p-2 hover:bg-gray-200"
                        >
                          {{ item.name }}
                        </li>
                      </ul>
                    </div>

                    <span v-if="errors.recipient_id" class="text-red-500">{{
                      errors.recipient_id[0]
                    }}</span>
                  </div>

                  <div class="mb-4">
                    <label
                      for="project"
                      class="block text-sm font-medium text-gray-700"
                    >
                      Проект:</label
                    >
                    <input
                      type="text"
                      id="project"
                      class="bg-gray-100 mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder=""
                      v-model="form.project"
                      :class="{ 'border-red-500': errors.project }"
                      readonly
                    />
                    <span v-if="errors.project" class="text-red-500 err">{{
                      errors.project[0]
                    }}</span>
                  </div>

                  <!-- Название лендинга -->
                  <div class="mb-4">
                    <label
                      for="name"
                      class="block text-sm font-medium text-gray-700"
                    >
                      Название лендинга:
                    </label>
                    <input
                      type="text"
                      id="name"
                      v-model="form.name"
                      placeholder="Введите название лендинга"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm"
                      :class="{ 'border-red-500': errors.name }"
                    />
                    <span v-if="errors.name" class="text-red-500">{{
                      errors.name[0]
                    }}</span>
                  </div>

                  <!-- URL лендинга -->
                  <div class="mb-4">
                    <label
                      for="url"
                      class="block text-sm font-medium text-gray-700"
                    >
                      URL лендинга:
                    </label>
                    <input
                      type="text"
                      id="url"
                      v-model="form.url"
                      placeholder="https://example.com"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm"
                      :class="{ 'border-red-500': errors.url }"
                    />
                    <span v-if="errors.url" class="text-red-500">{{
                      errors.url[0]
                    }}</span>
                  </div>

                  <!-- Краткое описание -->
                  <div class="mb-4">
                    <label
                      for="short_description"
                      class="block text-sm font-medium text-gray-700"
                    >
                      Краткое описание:
                    </label>
                    <input
                      type="text"
                      id="short_description"
                      v-model="form.short_description"
                      placeholder="Кратко о лендинге"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm"
                      :class="{ 'border-red-500': errors.short_description }"
                    />
                    <span v-if="errors.short_description" class="text-red-500">
                      {{ errors.short_description[0] }}
                    </span>
                  </div>

                  <!-- Описание -->
                  <div class="mb-4">
                    <label
                      for="description"
                      class="block text-sm font-medium text-gray-700"
                    >
                      Полное описание:
                    </label>
                    <textarea
                      id="description"
                      v-model="form.description"
                      rows="4"
                      placeholder="Полное описание лендинга"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm"
                      :class="{ 'border-red-500': errors.description }"
                    ></textarea>
                    <span v-if="errors.description" class="text-red-500">
                      {{ errors.description[0] }}
                    </span>
                  </div>

                  <!-- ID цели на переход с лендинга -->
                  <div class="mb-4">
                    <label
                      for="goal_click_id"
                      class="block text-sm font-medium text-gray-700"
                    >
                      ID цели (переход с лендинга):
                    </label>
                    <input
                      type="text"
                      id="goal_click_id"
                      v-model="form.goal_click_id"
                      placeholder="Например, 12345678"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm"
                      :class="{ 'border-red-500': errors.goal_click_id }"
                    />
                    <span v-if="errors.goal_click_id" class="text-red-500">
                      {{ errors.goal_click_id[0] }}
                    </span>
                  </div>

                  <!-- ID цели на подписку с лендинга -->
                  <div class="mb-4">
                    <label
                      for="goal_subscribe_id"
                      class="block text-sm font-medium text-gray-700"
                    >
                      ID цели (подписка с лендинга):
                    </label>
                    <input
                      type="text"
                      id="goal_subscribe_id"
                      v-model="form.goal_subscribe_id"
                      placeholder="Например, 87654321"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm"
                      :class="{ 'border-red-500': errors.goal_subscribe_id }"
                    />
                    <span v-if="errors.goal_subscribe_id" class="text-red-500">
                      {{ errors.goal_subscribe_id[0] }}
                    </span>
                  </div>

                  <div class="flex mt-6">
                    <button
                      type="submit"
                      class="flex-grow inline-flex justify-center py-2 mx-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-700 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                      :disabled="isLoading"
                    >
                      Добавить
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
            </Modal>
          </div>
        </div>
      </div>
    </div>

    <!--     <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">


            <h2>Карточка проекта: ID проекта -</h2>

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
