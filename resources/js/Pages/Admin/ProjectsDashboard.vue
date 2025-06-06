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
  project_auth: {
    type: Array,
    required: true,
  },
  projects: {
    type: Array,
    required: true,
  },
  pagination: {
    type: Object,
    required: true,
  },
});

const localProjects = ref([...props.projects]); // Локальная копия массива пользователей
const pagination = ref({ ...props.pagination }); // Инициализация локальной пагинации
const searchTerm = ref(""); // Переменная для хранения текста поиска
const searchCompletely = ref(); // Для сохранения значения состоаяния поиска при фильтрах
// Здесь мы сохраняем последние значения сортировки
const sortField = ref("created_at"); // Сортировка по умолчанию
const sortOrder = ref("desc"); // Порядок по умолчанию

//Загрузка текущего состояния таблицы Projects при изменении пагинации, поиска, сортировки
const fetchProjects = (
  page,
  searchTerm = null,
  sortField = null,
  sortOrder = null
) => {
  isLoading.value = true; // Состояние загрузки при запросе данных
  /*  alert(page); */

  axios
    .post(route("filtering_project"), {
      page: page,
      search: searchTerm,
      sortField: sortField,
      sortOrder: sortOrder,
    })
    .then(function (response) {
      localProjects.value = response.data.projects; // Обновляем список пользователей
      pagination.value = {
        current_page: response.data.pagination.current_page,
        per_page: response.data.pagination.per_page,
        total: response.data.pagination.total,
        last_page: response.data.pagination.last_page,
      }; // Обновляем данные пагинации

      // Проверка на случай, если текущая страница пустая
      if (
        localProjects.value.length === 0 &&
        pagination.value.current_page > 1
      ) {
        // Если текущая страница пустая, уменьшаем её на 1
        pagination.value.current_page--; // Переход на предыдущую страницу

        // Заново загружаем пользователей с новой текущей страницы
        return fetchProjects(
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
  fetchProjects(page, current_search, currentSortField, currentSortOrder);
};

//Метод поиска
const onSearch = () => {
  /* alert(searchTerm.value); */
  // Каждый раз при изменении поля поиска обновляем список пользователей
  /* alert(pagination.value.current_page); */
  searchCompletely.value = searchTerm.value;
  pagination.value.current_page = 1; // Сбрасываем на первую страницу
  fetchProjects(pagination.value.current_page, searchTerm.value); // Передаем новый поиск
};

//Метод сотрировки
const handleTableChange = (current_page, current_search, sorter) => {
  sortField.value = sorter.columnKey;
  sortOrder.value = sorter.order === "ascend" ? "asc" : "desc";
  current_page = pagination.value.current_page;
  current_search = searchCompletely.value;
  /* alert(searchCompletely.value); */
  fetchProjects(
    current_page,
    current_search,
    sorter.columnKey,
    sorter.order === "ascend" ? "asc" : "desc"
  );
};

// Первичная загрузка пользователей при монтировании компонента (если нужно)
onMounted(() => {
  fetchProjects(
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
    .post(route("axios_active_project", { id: id, checked: checked }))
    .then((response) => {
      /* // Если необходимо обновить вакансию работодателя, когда он становится активным
      const project = response.data.project; // Ответ содержит обновлённую вакансию работодателя
      const updatedProject = {
        ...localProjects.value.find((emp) => emp.id === id),
        ...project,
      }; // Объединяем старые поля и новые

      localProjects.value = localProjects.value.map((emp) =>
        emp.id === id ? updatedProject : emp
      ); */
      updateProjects();
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
const updateProjects = () => {
  fetchProjects(pagination.value.current_page); // Загружаем данные текущей страницы
};

// Наблюдатель для обновления локального массива пользователей при изменении props
/* watch(
  () => props.projects,
  (newProjects) => {
    localProjects.value = [...newProjects];
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
    title: "Клиент",
    dataIndex: "client_name",
    key: "client_name",
    sorter: true,
    /* width: "300px", */
  },
  {
    title: "Название проекта",
    dataIndex: "name",
    key: "name",
    sorter: true,
    /* width: "300px", */
  },
  {
    title: "Ссылка на проект",
    dataIndex: "link",
    key: "link",
    className: "text-center",
  },
  {
    title: "Дата создания",
    dataIndex: "created_at",
    key: "created_at",
    sorter: true,
  },
  {
    title: "Метрика",
    dataIndex: "yandex_metric_id",
    key: "yandex_metric_id",
  },
  {
    title: "Целевая метрика",
    dataIndex: "goal_id",
    key: "goal_id",
  },
  {
    title: "URL лендинга",
    dataIndex: "landing_url",
    key: "landing_url",
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
  user_id: "",
  client: "",
  name: "", // Название проекта
  link: "", // Ссылка на проект
  yandex_metric_id: "", // ID Яндекс Метрики
  goal_id: "", // Целевой идентификатор
  landing_url: "", // URL лендинга
  measurement_protocol_token: "",
  isActive: {},
});

// Сброс полей формы
const resetForm = () => {
  form.user_id = "";
  form.client = "";
  form.name = ""; // Сбрасываем название проекта
  form.link = ""; // Сбрасываем ссылку на проект
  form.yandex_metric_id = ""; // Сбрасываем ID Яндекс Метрики
  form.goal_id = ""; // Сбрасываем ID цели
  form.landing_url = ""; // Сбрасываем URL лендинга
  form.measurement_protocol_token = "";
  errors.value = {}; // Очищаем ошибки
  successMessage.value = ""; // Очищаем сообщение об успехе
  errDublMessage.value = ""; // Очищаем сообщение о дубликате
};

const errors = ref({});
const successMessage = ref("");
const errDublMessage = ref("");

const editProjectId = ref(null); // Для хранения id редактируемого пользователя
const deletProjectId = ref(null); // Для хранения id удаляемого пользователя

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
  deletProjectId.value = id;
  isModalDeleted.value = true;
}

//Метод добавленяи сущности в БД и отрисовку на странице без перезагрузки
const submitAddForm = () => {
  isLoading.value = true; // Устанавливаем состояние загрузки в true
  errors.value = {}; // Сбрасываем предыдущие ошибки
  successMessage.value = ""; // Сбрасываем предыдущее сообщение успеха
  errDublMessage.value = ""; // Сбрасываем предыдущие сообщения об ошибке

  axios
    .post(route("axios_add_project"), {
      user_id: form.user_id,
      name: form.name, // Название проекта
      link: form.link, // Ссылка на проект
      yandex_metric_id: form.yandex_metric_id, // Идентификатор Яндекс Метрики
      measurement_protocol_token: form.measurement_protocol_token, // Идентификатор цели
      goal_id: form.goal_id, // Идентификатор цели
      landing_url: form.landing_url, // URL лендинга
    })
    .then((response) => {
      console.log(response.data);
      // Добавляем новый проект в локальное состояние
      localProjects.value.unshift(response.data.project);

      successMessage.value = "Проект успешно добавлен!";

      // Обновление данных на странице
      updateProjects();

      const projectsPerPage = pagination.value.per_page; // Количество проектов на странице
      const totalProjectsAfterAdd = pagination.value.total + 1;

      // Обновляем общее количество проектов
      pagination.value.total = totalProjectsAfterAdd;

      // Проверяем состояние пагинации
      const maxPage = Math.ceil(totalProjectsAfterAdd / projectsPerPage);

      if (
        totalProjectsAfterAdd % projectsPerPage === 1 &&
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
          errDublMessage.value =
            error.response.data.message || "Ошибка при добавлении проекта.";
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
    .post(route("axios_edit_project"), { id: id })
    .then((response) => {
      const project = response.data.project;

      if (project) {
        form.user_id = project.user_id;
        form.client = project.client;
        form.name = project.name || ""; // Название проекта
        form.link = project.link || ""; // Ссылка на проект
        form.yandex_metric_id = project.yandex_metric_id || ""; // Яндекс Метрика
        form.goal_id = project.goal_id || ""; // Целевая метрика
        form.landing_url = project.landing_url || ""; // URL лендинга
        form.measurement_protocol_token =
          project.measurement_protocol_token || "";
        editProjectId.value = project.id; // Сохраняем ID проекта для дальнейшего обновления
        isModalEdit.value = true; // Открываем модальное окно
      } else {
        console.error("Проект не найден.");
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
  if (!editProjectId.value) {
    console.error("ID проекта не установлен!");
    return;
  }

  isLoading.value = true; // Устанавливаем состояние загрузки в true
  errors.value = {}; // Сбрасываем ошибки
  successMessage.value = ""; // Сбрасываем успешное сообщение

  axios
    .put(route("axios_update_project", { id: editProjectId.value }), {
      user_id: form.user_id,
      name: form.name, // Название проекта
      link: form.link, // Ссылка на проект
      yandex_metric_id: form.yandex_metric_id, // Идентификатор метрики
      goal_id: form.goal_id, // Идентификатор цели
      landing_url: form.landing_url, // URL лендинга
      measurement_protocol_token: form.measurement_protocol_token,
    })
    .then((response) => {
      const updatedProject = response.data.project;

      if (updatedProject) {
        successMessage.value = "Проект успешно обновлен!";

        // Обновляем локальное состояние проектов
        localProjects.value = localProjects.value.map((project) =>
          project.id === updatedProject.id ? updatedProject : project
        );

        updateProjects(); // Перезагрузка списка проектов
      } else {
        console.error("Обновленные данные проекта не получены.");
      }
    })
    .catch((error) => {
      if (error.response && error.response.data.errors) {
        errors.value = error.response.data.errors; // Сохраняем ошибки
        console.error("Ошибки валидации:", error.response.data.errors);
      } else if (error.request) {
        errDublMessage.value = "Проект с таким названием уже существует.";
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
const deleteProject = () => {
  isLoading.value = true; // Устанавливаем состояние загрузки
  axios
    .delete(route("axios_delete_project", { id: deletProjectId.value }))
    .then((response) => {
      // Удаляем пользователя из локального массива
      localProjects.value = localProjects.value.filter(
        (project) => project.id !== deletProjectId.value
      );

      updateProjects();

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

//Переменная блокировки выводимых id projects в таблице
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
    idsToDelete.map((id) => axios.delete(route("axios_delete_project", { id })))
  )
    .then(() => {
      // После успешного удаления обновляем список пользователей
      localProjects.value = localProjects.value.filter(
        (project) => !idsToDelete.includes(project.id)
      );
      selectedRowKeys.value = []; // Очищаем выбранные ключи
      successMessage.value = "Пользователи успешно удалены"; // уведомление об успехе
      fetchProjects(pagination.value.current_page); // Заносим обновленный список пользователей
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
  id: true, // ID проекта
  client_name: true,
  name: true, // Название проекта
  link: true, // Ссылка на проект
  yandex_metric_id: true, // ID Яндекс Метрики
  goal_id: true, // Идентификатор цели
  landing_url: true, // URL лендинга
  measurement_protocol_token: true, // Protocol Token
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
    visibleColumns.value.link = true;
    visibleColumns.value.yandex_metric_id = true;
    visibleColumns.value.goal_id = true;
    visibleColumns.value.landing_url = true;
    visibleColumns.value.measurement_protocol_token = true;
  } else {
    // Если переключаем на отображение минимального набора
    visibleColumns.value.link = true;
    visibleColumns.value.yandex_metric_id = true;
    visibleColumns.value.goal_id = true;
    visibleColumns.value.landing_url = true;
    visibleColumns.value.measurement_protocol_token = true;
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

const searchPeople = ref("");
const resultsPeople = ref([]); // Храним все результаты поиска
const selectedIdPeople = ref(null);

let issetMediaFile = false;

// Фильтруем результаты для отображения при вводе
const filteredResultsPeople = computed(() => {
  return resultsPeople.value.filter((item) => {
    const searchTerm = searchPeople.value.toLowerCase();

    // Поиск по id (сравнение строчных и числовых значений)
    const idMatches = item.user_id.toString().includes(searchTerm); // id может быть числом
    const firstNameMatches = item.name.toLowerCase().includes(searchTerm);
    /* const lastNameMatches = item.last_name.toLowerCase().includes(searchTerm); */

    // Возвращаем true, если совпадение найдено в любом из полей
    return idMatches || firstNameMatches;
  });
});

/* Выводим список в select по совпадению */
const onSearchPeople = async () => {
  if (searchPeople.value.length === 0) {
    resultsPeople.value = []; // Если поле поиска пустое, очищаем результаты
    return;
  }
  /* alert(searchPeople.value); */

  try {
    const response = await axios.get("/clients", {
      params: {
        query: searchPeople.value,
      },
    });
    resultsPeople.value = response.data; // Обновляем результаты поиска
  } catch (error) {
    console.error("Error while searching:", error);
    resultsPeople.value = []; // Очищаем результаты в случае ошибки
  }
};

/* Выбранный элемент устанавливаем в поле */
const selectItemPeople = (item) => {
  selectedIdPeople.value = item.id; // Устанавливаем ID выбранного элемента
  searchPeople.value = item.name; // Устанавливаем значение поискового поля на имя элемента
  resultsPeople.value = []; // Очищаем список результатов
  console.log("Selected ID:", selectedIdPeople.value);

  issetMediaFile = true;

  // Очистите список результатов после выбора
  filteredResultsPeople.value = [];
  searchPeople.value = ""; // Очищаем строку поиска

  form.client = item.name;
  form.user_id = item.id; // Передача ID
};
</script>

<template>
  <Head title="Клиенты" />

  <AuthenticatedLayout :project_auth="props.project_auth">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        <SelectOutlined :style="{ fontSize: '20px', verticalAlign: '0' }" />
        &nbsp; Управление проектами
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
              Добавить проект
            </button>

            <a-input
              placeholder="Введите название проекта, канал"
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
              :data-source="localProjects"
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
              title="Данные проекта"
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
              title="Удаление проекта"
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
              <form @submit.prevent="deleteProject" autocomplete="off">
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
              title="Форма редактирования проекта"
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
                  <!-- Клиент -->
                  <div class="mb-4">
                    <input
                      type="text"
                      v-model="searchPeople"
                      @input="onSearchPeople"
                      placeholder="Поиск клиента по id или Названию ..."
                      class="bg-yellow-100 border p-2 w-[100%] rounded-md"
                    />
                    <div
                      v-if="filteredResultsPeople.length > 0"
                      class="border mt-2 max-h-60 overflow-auto"
                    >
                      <ul>
                        <li
                          v-for="item in filteredResultsPeople"
                          :key="item.id"
                          @click="selectItemPeople(item)"
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
                      for="client"
                      class="block text-sm font-medium text-gray-700"
                    >
                      Клиент:</label
                    >
                    <input
                      type="text"
                      id="client"
                      class="bg-gray-100 mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder=""
                      v-model="form.client"
                      :class="{ 'border-red-500': errors.client }"
                      readonly
                    />
                    <span v-if="errors.client" class="text-red-500 err">{{
                      errors.client[0]
                    }}</span>
                  </div>

                  <!-- Название проекта -->
                  <div class="mb-4">
                    <label
                      for="name"
                      class="block text-sm font-medium text-gray-700"
                    >
                      Название проекта:
                    </label>
                    <input
                      type="text"
                      id="name"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder="Введите название проекта"
                      v-model="form.name"
                      :class="{ 'border-red-500': errors.name }"
                    />
                    <span v-if="errors.name" class="text-red-500 err">{{
                      errors.name[0]
                    }}</span>
                  </div>

                  <!-- Ссылка на проект -->
                  <div class="mb-4">
                    <label
                      for="link"
                      class="block text-sm font-medium text-gray-700"
                    >
                      Ссылка на проект:
                    </label>
                    <input
                      type="text"
                      id="link"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder="Введите ссылку на проект"
                      v-model="form.link"
                      :class="{ 'border-red-500': errors.link }"
                    />
                    <span v-if="errors.link" class="text-red-500 err">{{
                      errors.link[0]
                    }}</span>
                  </div>

                  <!-- ID Яндекс Метрики -->
                  <div class="mb-4">
                    <label
                      for="yandex_metric_id"
                      class="block text-sm font-medium text-gray-700"
                    >
                      ID Яндекс Метрики:
                    </label>
                    <input
                      type="text"
                      id="yandex_metric_id"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder="Введите идентификатор метрики"
                      v-model="form.yandex_metric_id"
                      :class="{ 'border-red-500': errors.yandex_metric_id }"
                    />
                    <span
                      v-if="errors.yandex_metric_id"
                      class="text-red-500 err"
                    >
                      {{ errors.yandex_metric_id[0] }}
                    </span>
                  </div>

                  <!-- Идентификатор цели -->
                  <div class="mb-4">
                    <label
                      for="goal_id"
                      class="block text-sm font-medium text-gray-700"
                    >
                      ID Цели:
                    </label>
                    <input
                      type="text"
                      id="goal_id"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder="Введите идентификатор цели"
                      v-model="form.goal_id"
                      :class="{ 'border-red-500': errors.goal_id }"
                    />
                    <span v-if="errors.goal_id" class="text-red-500 err">{{
                      errors.goal_id[0]
                    }}</span>
                  </div>

                  <!-- URL лендинга -->
                  <div class="mb-4">
                    <label
                      for="landing_url"
                      class="block text-sm font-medium text-gray-700"
                    >
                      URL лендинга:
                    </label>
                    <input
                      type="text"
                      id="landing_url"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder="Введите URL лендинга"
                      v-model="form.landing_url"
                      :class="{ 'border-red-500': errors.landing_url }"
                    />
                    <span v-if="errors.landing_url" class="text-red-500 err">
                      {{ errors.landing_url[0] }}
                    </span>
                  </div>

                  <!-- Measurement Protocol Token -->
                  <div class="mb-4">
                    <label
                      for="measurement_protocol_token"
                      class="block text-sm font-medium text-gray-700"
                    >
                      Measurement Protocol Token:
                    </label>
                    <input
                      type="text"
                      id="measurement_protocol_token"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder="Введите токен Measurement Protocol"
                      v-model="form.measurement_protocol_token"
                      :class="{
                        'border-red-500': errors.measurement_protocol_token,
                      }"
                    />
                    <span
                      v-if="errors.measurement_protocol_token"
                      class="text-red-500 err"
                    >
                      {{ errors.measurement_protocol_token[0] }}
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
              title="Форма добавления проекта"
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
                  <!-- Клиент -->
                  <div class="mb-4">
                    <input
                      type="text"
                      v-model="searchPeople"
                      @input="onSearchPeople"
                      placeholder="Поиск клиента по id или Названию ..."
                      class="bg-yellow-100 border p-2 w-[100%] rounded-md"
                    />
                    <div
                      v-if="filteredResultsPeople.length > 0"
                      class="border mt-2 max-h-60 overflow-auto"
                    >
                      <ul>
                        <li
                          v-for="item in filteredResultsPeople"
                          :key="item.id"
                          @click="selectItemPeople(item)"
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
                      for="client"
                      class="block text-sm font-medium text-gray-700"
                    >
                      Клиент:</label
                    >
                    <input
                      type="text"
                      id="client"
                      class="bg-gray-100 mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder=""
                      v-model="form.client"
                      :class="{ 'border-red-500': errors.client }"
                      readonly
                    />
                    <span v-if="errors.client" class="text-red-500 err">{{
                      errors.client[0]
                    }}</span>
                  </div>

                  <!-- Название проекта -->
                  <div class="mb-4">
                    <label
                      for="name"
                      class="block text-sm font-medium text-gray-700"
                    >
                      Название проекта:
                    </label>
                    <input
                      type="text"
                      id="name"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder="Введите название проекта"
                      v-model="form.name"
                      :class="{ 'border-red-500': errors.name }"
                    />
                    <span v-if="errors.name" class="text-red-500 err">{{
                      errors.name[0]
                    }}</span>
                  </div>

                  <!-- Ссылка на проект -->
                  <div class="mb-4">
                    <label
                      for="link"
                      class="block text-sm font-medium text-gray-700"
                    >
                      Ссылка на проект:
                    </label>
                    <input
                      type="text"
                      id="link"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder="Введите ссылку на проект"
                      v-model="form.link"
                      :class="{ 'border-red-500': errors.link }"
                    />
                    <span v-if="errors.link" class="text-red-500 err">{{
                      errors.link[0]
                    }}</span>
                  </div>

                  <!-- ID Яндекс Метрики -->
                  <div class="mb-4">
                    <label
                      for="yandex_metric_id"
                      class="block text-sm font-medium text-gray-700"
                    >
                      ID Яндекс Метрики:
                    </label>
                    <input
                      type="text"
                      id="yandex_metric_id"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder="Введите идентификатор метрики"
                      v-model="form.yandex_metric_id"
                      :class="{ 'border-red-500': errors.yandex_metric_id }"
                    />
                    <span
                      v-if="errors.yandex_metric_id"
                      class="text-red-500 err"
                    >
                      {{ errors.yandex_metric_id[0] }}
                    </span>
                  </div>

                  <!-- Идентификатор цели -->
                  <div class="mb-4">
                    <label
                      for="goal_id"
                      class="block text-sm font-medium text-gray-700"
                    >
                      ID Цели:
                    </label>
                    <input
                      type="text"
                      id="goal_id"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder="Введите идентификатор цели"
                      v-model="form.goal_id"
                      :class="{ 'border-red-500': errors.goal_id }"
                    />
                    <span v-if="errors.goal_id" class="text-red-500 err">{{
                      errors.goal_id[0]
                    }}</span>
                  </div>

                  <!-- URL лендинга -->
                  <div class="mb-4">
                    <label
                      for="landing_url"
                      class="block text-sm font-medium text-gray-700"
                    >
                      URL лендинга:
                    </label>
                    <input
                      type="text"
                      id="landing_url"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder="Введите URL лендинга"
                      v-model="form.landing_url"
                      :class="{ 'border-red-500': errors.landing_url }"
                    />
                    <span v-if="errors.landing_url" class="text-red-500 err">
                      {{ errors.landing_url[0] }}
                    </span>
                  </div>

                  <!-- Measurement Protocol Token -->
                  <div class="mb-4">
                    <label
                      for="measurement_protocol_token"
                      class="block text-sm font-medium text-gray-700"
                    >
                      Measurement Protocol Token:
                    </label>
                    <input
                      type="text"
                      id="measurement_protocol_token"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
                      placeholder="Введите токен Measurement Protocol"
                      v-model="form.measurement_protocol_token"
                      :class="{
                        'border-red-500': errors.measurement_protocol_token,
                      }"
                    />
                    <span
                      v-if="errors.measurement_protocol_token"
                      class="text-red-500 err"
                    >
                      {{ errors.measurement_protocol_token[0] }}
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
