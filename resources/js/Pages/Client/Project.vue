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

import AccordionItem from "@/Pages/Client/AccordionItem.vue";

import { DatePicker, Radio } from "ant-design-vue";
import { nextTick } from "vue";
import * as XLSX from "xlsx";

const { RangePicker } = DatePicker;

import {
  Chart,
  BarController,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
} from "chart.js";

import {
  LineController,
  LineElement,
  PointElement,
  TimeScale,
  Filler,
} from "chart.js";

// Регистрация необходимых компонентов для Chart.js
Chart.register(
  BarController,
  BarElement,
  CategoryScale,
  LinearScale,

  Tooltip, // Добавьте эти строки:
  LineController,
  LineElement,
  PointElement,
  TimeScale,
  Filler
);

import Modal from "@/Components/ModalCrud.vue";

import Loader from "@/Components/Loader.vue"; // Импортируем компонент предзагрузчика

const apiUrl = import.meta.env.VITE_API_URL;

/** Экспорт в PDF */
import html2pdf from "html2pdf.js";

const pdfContent = ref(null);

const isGeneratingPDF = ref(false);

const downloadPDF = async () => {
  const element = pdfContent.value;
  if (!element) return;

  isGeneratingPDF.value = true;

  const options = {
    margin: [0.2, 0.5, 0.5, 0.5], // top, left, bottom, right
    filename: "report.pdf",
    image: { type: "jpeg", quality: 0.98 },
    html2canvas: { scale: 2 },
    jsPDF: { unit: "in", format: "a4", orientation: "portrait" },
  };

  await new Promise((resolve) => {
    html2pdf().from(element).set(options).save().then(resolve);
  });

  isGeneratingPDF.value = false;
};

const pdfContent2 = ref(null);

const downloadPDF2 = async () => {
  const element2 = pdfContent2.value;
  if (!element2) return;

  isGeneratingPDF.value = true;

  const options = {
    margin: [0.2, 0.5, 0.5, 0.5],
    filename: "report.pdf",
    image: { type: "jpeg", quality: 0.98 },
    html2canvas: { scale: 2 },
    jsPDF: { unit: "in", format: "a4", orientation: "portrait" },
  };

  await new Promise((resolve) => {
    html2pdf().from(element2).set(options).save().then(resolve);
  });

  isGeneratingPDF.value = false;
};

/** */

const props = defineProps({
  rights: {
    type: Boolean,
  },

  project: {
    type: Array,
    required: true,
  },
  user_auth: {
    type: Array,
    required: true,
  },
  subscribers: {
    type: Array,
    required: true,
  },

  landings: {
    type: Array,
    required: true,
  },
  chartData: {
    type: Array,
    required: true,
  },
});

const localSubscribers = ref([...props.subscribers.data]);
const pagination = ref({
  current_page: props.subscribers.current_page,
  last_page: props.subscribers.last_page,
  per_page: props.subscribers.per_page,
  total: props.subscribers.total,
});

/** Для таблицы лендингов */

const localLandings = ref([...props.landings.data]);
const pagination_landings = ref({
  current_page: props.landings.current_page,
  last_page: props.landings.last_page,
  per_page: props.landings.per_page,
  total: props.landings.total,
});
/**/

const base_participants_count = ref(null);

const firstDate = ref(null);
const participants_count_from_channel = ref(null);

const searchTerm = ref(""); // Переменная для хранения текста поиска
const searchCompletely = ref(); // Для сохранения значения состоаяния поиска при фильтрах
// Здесь мы сохраняем последние значения сортировки
const sortField = ref("created_at"); // Сортировка по умолчанию
const sortOrder = ref("desc"); // Порядок по умолчанию

//Загрузка текущего состояния таблицы Subscribers при изменении пагинации, поиска, сортировки
const fetchSubscribers = (
  page,
  searchTerm = null,
  sortField = null,
  sortOrder = null,
  perPage = pagination.value.per_page
) => {
  isLoading.value = true;

  axios
    .post(route("filtering_subscribers"), {
      project_id: props.project.id,
      page,
      perPage,
      search: searchTerm,
      sortField,
      sortOrder,
      is_active: isActiveFilter.value, // 🔹 Добавляем сюда
    })
    .then((response) => {
      localSubscribers.value = response.data.subscribers;
      pagination.value = {
        current_page: response.data.pagination.current_page,
        per_page: response.data.pagination.per_page,
        total: response.data.pagination.total,
        last_page: response.data.pagination.last_page,
      };

      firstDate.value = response.data.firstDate;

      participants_count_from_channel.value =
        response.data.participants_count_from_channel;

      if (
        localSubscribers.value.length === 0 &&
        pagination.value.current_page > 1
      ) {
        pagination.value.current_page--;
        return fetchSubscribers(
          pagination.value.current_page,
          searchTerm,
          sortField,
          sortOrder
        );
      }
    })
    .catch((error) => {
      console.error("Ошибка при загрузке подписчиков:", error);
    })
    .finally(() => {
      isLoading.value = false;
    });
};

// Пагинация - Инициализация обработки изменения страницы
const handlePageChange = (page, current_search, sorter = {}) => {
  const currentSortField = sortField.value;
  const currentSortOrder = sortOrder.value;
  current_search = searchCompletely.value;

  // Загружаем пользователей с учетом текущей страницы и сортировки
  fetchSubscribers(page, current_search, currentSortField, currentSortOrder);
};

const handlePageSizeChange = (currentPage, newPageSize) => {
  pagination.value.per_page = newPageSize;
  pagination.value.current_page = currentPage;
  fetchSubscribers(
    pagination.value.current_page,
    searchCompletely.value,
    sortField.value,
    sortOrder.value,
    newPageSize
  );
};

//Метод поиска
const onSearch = () => {
  /* alert(searchTerm.value); */
  // Каждый раз при изменении поля поиска обновляем список пользователей
  /* alert(pagination.value.current_page); */
  searchCompletely.value = searchTerm.value;
  pagination.value.current_page = 1; // Сбрасываем на первую страницу
  fetchSubscribers(pagination.value.current_page, searchTerm.value); // Передаем новый поиск
};

//Метод сотрировки
const handleTableChange = (current_page, current_search, sorter) => {
  sortField.value = sorter.columnKey;
  sortOrder.value = sorter.order === "ascend" ? "asc" : "desc";
  current_page = pagination.value.current_page;
  current_search = searchCompletely.value;
  /* alert(searchCompletely.value); */
  fetchSubscribers(
    current_page,
    current_search,
    sorter.columnKey,
    sorter.order === "ascend" ? "asc" : "desc"
  );
};

/** Для лендингов фильтрация */

const searchTermLandings = ref("");
const searchCompletelyLandings = ref("");
const sortFieldLandings = ref("created_at");
const sortOrderLandings = ref("desc");

//Загрузка текущего состояния таблицы Subscribers при изменении пагинации, поиска, сортировки
const fetchLandings = (
  page,
  searchTerm = null,
  sortField = null,
  sortOrder = null,
  perPage = pagination_landings.value.per_page
) => {
  isLoading.value = true;

  axios
    .post(route("filtering_landings"), {
      project_id: props.project.id,
      page,
      perPage,
      search: searchTerm,
      sortField,
      sortOrder,
      is_active: isActiveFilter.value, // 🔹 Добавляем сюда
    })
    .then((response) => {
      localLandings.value = response.data.landings;
      pagination_landings.value = {
        current_page: response.data.pagination_landings.current_page,
        per_page: response.data.pagination_landings.per_page,
        total: response.data.pagination_landings.total,
        last_page: response.data.pagination_landings.last_page,
      };

      if (
        localLandings.value.length === 0 &&
        pagination.value.current_page > 1
      ) {
        pagination.value.current_page--;
        return fetchLandings(
          pagination.value.current_page,
          searchTerm,
          sortField,
          sortOrder
        );
      }
    })
    .catch((error) => {
      console.error("Ошибка при загрузке подписчиков:", error);
    })
    .finally(() => {
      isLoading.value = false;
    });
};

// Поиск
const onSearchLandings = () => {
  searchCompletelyLandings.value = searchTermLandings.value;
  pagination_landings.value.current_page = 1;
  fetchLandings(
    pagination_landings.value.current_page,
    searchCompletelyLandings.value,
    sortFieldLandings.value,
    sortOrderLandings.value
  );
};

const handlePageChangeLandings = (page) => {
  fetchLandings(
    page,
    searchCompletelyLandings.value,
    sortFieldLandings.value,
    sortOrderLandings.value
  );
};

const handlePageSizeChangeLandings = (currentPage, newPageSize) => {
  pagination_landings.value.per_page = newPageSize;
  pagination_landings.value.current_page = currentPage;
  fetchLandings(
    currentPage,
    searchCompletelyLandings.value,
    sortFieldLandings.value,
    sortOrderLandings.value,
    newPageSize
  );
};

const handleTableChangeLandings = (pagination, filters, sorter) => {
  sortFieldLandings.value = sorter.columnKey;
  sortOrderLandings.value = sorter.order === "ascend" ? "asc" : "desc";
  fetchLandings(
    pagination_landings.value.current_page,
    searchCompletelyLandings.value,
    sortFieldLandings.value,
    sortOrderLandings.value
  );
};

/** */

const showGraph = ref(false);
const groupBy = ref(null);

const chartData = ref(props.chartData || []);
const dateRange = ref([props.defaultStart, props.defaultEnd]);
const grouping = ref(props.defaultGrouping || "hour");
const isLoading = ref(false);
let total = ref();

const isActiveFilter = ref(1); // 1 - подписчики, 0 - отписчики

const switchActiveFilter = (status) => {
  isActiveFilter.value = status;
  fetchSubscribers(1, searchCompletely.value, sortField.value, sortOrder.value);
  fetchChartData();
};

// В методе fetchChartData вызываем дополнительно fetchCumulativeChartData
const fetchChartData = async () => {
  isLoading.value = true;

  try {
    const response = await axios.get(
      `/client/projects/${props.project.id}/chart`,
      {
        params: {
          startDate: dateRange.value[0],
          endDate: dateRange.value[1],
          grouping: groupBy.value,
          is_active: isActiveFilter.value,
        },
      }
    );

    chartData.value = response.data.chartData;
    total.value = response.data.total;

    nextTick(() => {
      buildChart();
    });

    // Загружаем отдельно накопительный график
    await fetchCumulativeChartData();
  } catch (error) {
    console.error("Ошибка при загрузке графика:", error);
  } finally {
    isLoading.value = false;
  }
};

// Убираем строки с count === 0
const filteredData = computed(() => {
  return chartData.value.filter((item) => item.count > 0);
});

function exportToExcel() {
  const data = filteredData.value.map((row) => ({
    Период: row.period,
    Количество: row.count,
  }));

  const total = data.reduce((sum, row) => sum + row["Количество"], 0);
  data.push({ Период: "Итого", Количество: total });

  // Создаем пустой лист
  const worksheet = XLSX.utils.aoa_to_sheet([]);

  // Вставляем динамический заголовок в первую строку
  XLSX.utils.sheet_add_aoa(worksheet, [[label.value]], { origin: "A1" });

  // Вставляем таблицу начиная с A3 (третья строка)
  XLSX.utils.sheet_add_json(worksheet, data, {
    origin: "A3",
    skipHeader: false,
  });

  const workbook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(workbook, worksheet, [[label.value]]);
  XLSX.writeFile(workbook, "chart_data.xlsx");
}

function exportToExcel2() {
  const data = tableData.value.map((row) => ({
    Период: row.period,
    Подписались: row.subscriptions,
    Отписались: row.unsubscriptions,
    "Результат за период": row.subscriptions - row.unsubscriptions,
    Накопление: row.cumulative,
  }));

  // Итоговая строка
  const totalSubscriptions = data.reduce(
    (sum, row) => sum + row["Подписались"],
    0
  );
  const totalUnsubscriptions = data.reduce(
    (sum, row) => sum + row["Отписались"],
    0
  );
  const totalResult = data.reduce(
    (sum, row) => sum + row["Результат за период"],
    0
  );
  const totalCumulative = data.length ? data[data.length - 1]["Накопление"] : 0;

  data.push({
    Период: "Итого",
    Подписались: totalSubscriptions,
    Отписались: totalUnsubscriptions,
    "Результат за период": totalResult,
    Накопление: totalCumulative,
  });

  // 1. Создаём пустой лист
  const worksheet = XLSX.utils.aoa_to_sheet([]);

  // 2. Вставляем заголовок в первую строку
  XLSX.utils.sheet_add_aoa(
    worksheet,
    [["Анализ прироста подписчиков (накопительно)"]],
    { origin: "A1" }
  );

  // 3. Вставляем таблицу начиная с A3 (то есть с третьей строки)
  XLSX.utils.sheet_add_json(worksheet, data, {
    origin: "A3",
    skipHeader: false,
  });

  const workbook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(workbook, worksheet, "Подписчики");
  XLSX.writeFile(workbook, "subscribers_table.xlsx");
}

const cumulativeChartRef = ref(null);
let cumulativeChartInstance = null;

// Первичная загрузка пользователей при монтировании компонента (если нужно)
onMounted(() => {
  fetchSubscribers(
    pagination.value.current_page,
    "",
    sortField.value,
    sortOrder.value
  );

  fetchLandings(
    pagination_landings.value.current_page,
    "",
    sortField.value,
    sortOrder.value
  );

  groupBy.value = "hour"; // <- Устанавливаем группировку по умолчанию
  showGraph.value = true; // <- Показываем график сразу
  fetchChartData(); // <- Загружаем данные для графика сразу
});

watch([groupBy, dateRange], () => {
  showGraph.value = true;
  /* alert(showGraph.value); */
  if (showGraph.value) {
    showGraph.value = true; // 👈 Показать canvas
    fetchChartData();
  }
});

/** Устанавливаем второй wath для отлавливания открытия аккардиона (из дочернего компонента) и сразу отрисовывать график - чтобы не скрыт был */
const analyticsOpen = ref(false); // состояние аккордеона

watch(analyticsOpen, (open) => {
  if (open) {
    showGraph.value = true;
    fetchChartData(); // например, загружаем график
  }
});

// Метод обновления списка пользователей
const updateSubscribers = () => {
  fetchSubscribers(pagination.value.current_page); // Загружаем данные текущей страницы
};

// Наблюдатель для обновления локального массива пользователей при изменении props
/* watch(
  () => props.subscribers,
  (newSubscribers) => {
    localSubscribers.value = [...newSubscribers];
  }
); */

//Исходные колонки вывода в таблице Ant Desing
const columns = ref([
  {
    title: "telegram_id",
    dataIndex: "telegram_user_id",
    key: "telegram_user_id",
    sorter: true,
  },

  {
    title: "Имя",
    dataIndex: "first_name",
    key: "first_name",
    sorter: true,
    width: "200px",
  },
  {
    title: "Фамилия",
    dataIndex: "last_name",
    key: "last_name",
    sorter: true,
    width: "200px",
  },

  {
    title: "Логин",
    dataIndex: "username",
    key: "username",
    sorter: true,
  },

  {
    title: "Номер телефона",
    dataIndex: "phone",
    key: "phone",
    sorter: true,
  },

  {
    title: "Статус подписки",
    dataIndex: "is_active",
    key: "is_active",
    sorter: true,
  },

  {
    title: "Дата подписки/отписки",
    dataIndex: "updated_at",
    key: "updated_at",
    sorter: true,
    width: "300px",
  },

  /*   {
    title: "Действие",
    key: "action",
  }, */
]);

//Исходные колонки вывода в таблице Ant Desing
const columns_landings = ref([
  {
    title: "Название",
    dataIndex: "name",
    key: "name",
    sorter: true,
  },

  {
    title: "Ссылка",
    dataIndex: "url",
    key: "url",
    sorter: true,
    width: "200px",
  },

  {
    title: "ID цели (переход с лендинга)",
    dataIndex: "goal_click_id",
    key: "goal_click_id",
    sorter: true,
  },

  {
    title: "ID цели (подписка с лендинга): ",
    dataIndex: "goal_subscribe_id",
    key: "goal_subscribe_id",
    sorter: true,
  },
]);

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

/* Графики */

const label = computed(() => {
  return isActiveFilter.value === 1 ? "Подписки" : "Отписки";
});

const label_user = computed(() => {
  return isActiveFilter.value === 1 ? "Подписчиков" : "Отписчиков";
});

const onDateChange = (dates) => {
  if (!dates || dates.length === 0) {
    // Если диапазон очищен
    groupBy.value = "hour"; // Сброс группировки на 'day'
    dateRange.value = []; // Очищаем текущий диапазон
  } else {
    dateRange.value = dates;
  }

  // Перезапросить данные графика
  fetchChartData();
};

// Реактивные данные для графика
const chartRef = ref(null);
let chartInstance = null;

// Функция для построения графика
const buildChart = () => {
  if (chartInstance) {
    chartInstance.destroy();
  }

  const ctx = chartRef.value.getContext("2d");

  const labels = chartData.value.map((item) => item.period);
  const data = chartData.value.map((item) => item.count);

  chartInstance = new Chart(ctx, {
    type: "bar",
    data: {
      labels,
      datasets: [
        {
          label: label.value,
          data,
          backgroundColor: "rgba(75, 192, 192, 0.2)",
          borderColor: "rgba(75, 192, 192, 1)",
          borderWidth: 1,
        },
      ],
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true,
        },
      },
    },
  });
};

// Добавляем новую реактивную переменную для данных накопительного графика
const cumulativeChartData = ref([]);
const tableData = ref([]);

// Функция загрузки данных для накопительного графика из другого эндпоинта
const fetchCumulativeChartData = async () => {
  isLoading.value = true;

  try {
    const response = await axios.get(
      `/client/projects/${props.project.id}/cumulative-chart`, // <- другой эндпоинт
      {
        params: {
          startDate: dateRange.value[0],
          endDate: dateRange.value[1],
          grouping: groupBy.value,
          is_active: isActiveFilter.value,
        },
      }
    );

    cumulativeChartData.value = response.data.cumulativeChart;

    /** Обновляем общее количество подписчиков с учетом накопления за период = из базы первоначальное + собранное за весь период */
    base_participants_count.value =
      props.project.participants_count +
      (cumulativeChartData.value.length
        ? cumulativeChartData.value[cumulativeChartData.value.length - 1].count
        : 0);

    const rawTableData = response.data.tableData;

    tableData.value = rawTableData.filter((row) => {
      return row.subscriptions !== 0 || row.unsubscriptions !== 0;
    });

    nextTick(() => {
      buildCumulativeChart();
    });
  } catch (error) {
    console.error("Ошибка при загрузке накопительного графика:", error);
  } finally {
    isLoading.value = false;
  }
};

// Модифицируем buildCumulativeChart чтобы использовать cumulativeChartData вместо chartData
const buildCumulativeChart = () => {
  if (cumulativeChartInstance) {
    cumulativeChartInstance.destroy();
  }

  const ctx = cumulativeChartRef.value.getContext("2d");

  const labels = cumulativeChartData.value.map((item) => item.period);
  const originalCumulative = cumulativeChartData.value.map(
    (item) => item.count
  );

  // Стартовая точка фиксирована на 500
  /* const base = props.project.participants_count; */

  /** Хотел ранее испрользовать offset чтобы если канал сразу примнимает много подписчиков чтобы они не считались в статистике
   поэтому первое значение обнулял на этуже величину
    */
  //const offset = originalCumulative[0] || 0;

  const shiftedCumulative = originalCumulative.map((value) => {
    return value;
    /* return value - offset; */
    /*  return base + (value - offset); */
  });

  cumulativeChartInstance = new Chart(ctx, {
    type: "line",
    data: {
      labels,
      datasets: [
        {
          label: "Накопленные подписчики",
          data: shiftedCumulative,
          backgroundColor: "rgba(54, 162, 235, 0.2)",
          borderColor: "rgba(54, 162, 235, 1)",
          fill: false,
          tension: 0.1,
        },
      ],
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: false,
        },
      },
    },
  });
};
</script>

<template>
  <Head title="Клиенты" />

  <AuthenticatedLayout :user_auth="props.user_auth">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        <SelectOutlined :style="{ fontSize: '20px', verticalAlign: '0' }" />
        &nbsp; Проект
      </h2>
    </template>

    <div v-if="project.is_active && project.user_active">
      <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <h1
            class="text-1xl md:text-2xl font-bold text-white bg-[#7B869B] px-6 py-4 shadow-xl rounded-[5px]"
          >
            <!-- Контент блока -->
            Проект: {{ project.name }}
          </h1>
        </div>
      </div>

      <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div class="flex flex-col lg:flex-row gap-4">
            <!-- Блок 111 (1/3 ширины) -->
            <div
              class="basis-1/3 bg-white overflow-hidden shadow-sm sm:rounded-lg p-4"
            >
              <img
                :src="project.photo"
                class="rounded-[100%] w-[300px] h-[300px] mx-auto"
                alt=""
              />
            </div>
            <!-- Блок 222 (занимает оставшееся пространство 2/3) -->
            <div
              class="flex-1 bg-white overflow-hidden shadow-sm sm:rounded-lg p-4"
            >
              <!--  <p><b>Название проекта:</b> {{ project.name }}</p> -->
              <p><b>ID проекта:</b>&nbsp; {{ project.channel_id }}</p>
              <p>
                <b>Ссылка на проект:</b>&nbsp;
                {{ project.link ?? "Отсутствует" }}
              </p>
              <p>
                <b>Описание проекта:</b>&nbsp;
                {{ project.about ?? "Отсутствует" }}
              </p>
              <p>
                <b>Тип проекта:</b>&nbsp;
                <span v-if="project.type === 'channel'">Канал</span>
                <span v-else>Группа</span>
              </p>
              <p>
                <b>Приватность проекта:</b>&nbsp;
                <span v-if="project.privacy === 'private'">Закрытый</span>
                <span v-else>Публичный</span>
              </p>
              <p>
                <b>Количество подписчиков:</b>&nbsp;{{
                  participants_count_from_channel
                }}
              </p>
            </div>
          </div>
        </div>
      </div>
      <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
              <AccordionItem title="Аналитика" v-model="analyticsOpen">
                <section>
                  <div class="mt-8 text-center">
                    Дата/время начала сбора данных по проекту:
                    {{ formatDate(firstDate) }}
                  </div>
                  <!-- Выбор даты и интервала -->
                  <div
                    class="flex flex-col lg:flex-row items-center justify-center gap-4 mt-10"
                  >
                    <RangePicker
                      v-model:value="dateRange"
                      @change="onDateChange"
                    />
                    <a-radio-group v-model:value="groupBy" button-style="solid">
                      <a-radio-button value="hour">По часам</a-radio-button>
                      <a-radio-button value="day">По дням</a-radio-button>
                      <a-radio-button value="month">По месяцам</a-radio-button>
                      <a-radio-button value="year">По годам</a-radio-button>
                    </a-radio-group>
                  </div>

                  <div class="ml-2 sm:ml-6">
                    <div ref="pdfContent">
                      <h3
                        class="relative text-[1.1rem] font-semibold text-black pl-4 border-l-8 border-[#45A0F2] bg-white py-3 mb-6 after:content-[''] after:block after:w-full after:h-[2px] after:bg-black after:mt-4 !bg-[#ffffd9] mt-12"
                      >
                        Анализ динамики {{ label }}
                      </h3>

                      <div class="flex justify-center my-4">
                        <a-space>
                          <a-button
                            v-if="!isGeneratingPDF"
                            :type="isActiveFilter === 1 ? 'primary' : 'default'"
                            :style="
                              isActiveFilter === 1
                                ? 'background-color: #52c41a; border-color: #52c41a; color: white'
                                : ''
                            "
                            @click="switchActiveFilter(1)"
                          >
                            Подписка
                          </a-button>

                          <a-button
                            v-if="!isGeneratingPDF"
                            :type="isActiveFilter === 0 ? 'primary' : 'default'"
                            :style="
                              isActiveFilter === 0
                                ? 'background-color: #ff4d4f; border-color: #ff4d4f; color: white'
                                : ''
                            "
                            @click="switchActiveFilter(0)"
                          >
                            Отписка
                          </a-button>
                        </a-space>
                      </div>
                      <!-- Заголовок динамический -->
                      <div class="text-center mt-10 mb-10 font-bold">
                        <span>
                          {{ label }} на проект за выбранный период (с
                          {{ formatDate(firstDate) }} - по
                          {{
                            groupBy === "hour"
                              ? "часам"
                              : groupBy === "day"
                              ? "дням"
                              : groupBy === "month"
                              ? "месяцам"
                              : groupBy === "year"
                              ? "годам"
                              : groupBy
                          }})
                        </span>
                        - <span>{{ total }}</span> чел
                      </div>

                      <div class="mt-6">
                        <!-- График -->
                        <canvas
                          ref="chartRef"
                          class="w-full sm:w-2/3 h-[300px]"
                          v-if="showGraph"
                        ></canvas>
                      </div>

                      <div class="mt-10">
                        <table v-if="filteredData.length">
                          <thead>
                            <tr>
                              <th class="text-center">Период</th>
                              <th class="text-center">Количество</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="item in filteredData" :key="item.period">
                              <td class="text-center">{{ item.period }}</td>
                              <td class="text-center">{{ item.count }}</td>
                            </tr>
                            <tr>
                              <td class="text-center">
                                <strong>Итого</strong>
                              </td>
                              <td class="text-center">
                                <strong>{{ total }}</strong>
                              </td>
                            </tr>
                          </tbody>
                        </table>

                        <p v-else>Нет данных для отображения.</p>
                        <button
                          v-if="!isGeneratingPDF"
                          @click="exportToExcel"
                          class="export-btn rounded-[5px] bg-[#FEB72D] py-[7px] px-[10px] mt-2 text-[0.9rem]"
                        >
                          Экспорт таблицы в Excel
                        </button>
                      </div>
                      <a-button
                        v-if="!isGeneratingPDF"
                        type="primary"
                        class="my-4"
                        @click="downloadPDF"
                      >
                        Скачать PDF отчёт
                      </a-button>
                    </div>

                    <div>
                      <h3
                        class="relative text-[1.1rem] font-semibold text-black pl-4 border-l-8 border-[#45A0F2] bg-white py-3 mb-6 after:content-[''] after:block after:w-full after:h-[2px] after:bg-black after:mt-4 !bg-[#ffffd9] mt-12"
                      >
                        Детализация {{ label_user }}
                      </h3>

                      <a-input
                        placeholder="Введите название имени подписчика для поиска"
                        v-model:value="searchTerm"
                        @input="onSearch"
                        style="margin-bottom: 16px"
                        class="rounded mt-5"
                      />

                      <a-table
                        :row-key="(record) => record.id"
                        :data-source="localSubscribers"
                        :columns="columns"
                        :pagination="false"
                        :row-selection="rowSelection"
                        @change="handleTableChange"
                        class="w-full overflow-x-auto mt-9 z-0"
                      >
                        <template #bodyCell="{ column, record }">
                          <template v-if="column.key === 'updated_at'">
                            {{ formatDate(record.updated_at) }}
                          </template>

                          <template v-if="column.key === 'is_active'">
                            <span
                              v-if="record.is_active == 1"
                              class="subscribed"
                              >подписан</span
                            >
                            <span v-else class="unsubscribed">отписан</span>
                          </template>
                        </template>
                      </a-table>

                      <a-pagination
                        :current="pagination.current_page"
                        :pageSize="pagination.per_page"
                        :total="pagination.total"
                        @change="handlePageChange"
                        @showSizeChange="handlePageSizeChange"
                        style="margin-top: 20px"
                      />
                    </div>
                  </div>

                  <div ref="pdfContent2">
                    <h3
                      class="relative text-[1.1rem] font-semibold text-black pl-4 border-l-8 border-[#45A0F2] bg-white py-3 mb-6 after:content-[''] after:block after:w-full after:h-[2px] after:bg-black after:mt-4 !bg-[#ffffd9] mt-12"
                    >
                      Общая динамика аудитории проекта
                    </h3>

                    <div class="mt-10">
                      <h4 class="text-lg font-semibold text-center mb-4">
                        График роста аудитории проекта
                      </h4>
                      <canvas
                        ref="cumulativeChartRef"
                        class="w-full sm:w-2/3 h-[300px]"
                      ></canvas>
                    </div>

                    <div class="p-4 mt-16">
                      <h4 class="text-xl font-semibold mb-4 text-center">
                        Эффективность привлечения подписчиков
                      </h4>

                      <table
                        class="min-w-full table-auto border-collapse border border-gray-300"
                      >
                        <thead class="bg-gray-100">
                          <tr>
                            <th class="border px-4 py-2 text-center">Период</th>
                            <th class="border px-4 py-2 text-center">
                              Подписались
                            </th>
                            <th class="border px-4 py-2 text-center">
                              Отписались
                            </th>
                            <th class="border px-4 py-2 text-center">
                              Результат за период
                            </th>
                            <th class="border px-4 py-2 text-center">
                              Накопление
                            </th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr
                            v-for="(row, index) in tableData"
                            :key="index"
                            class="hover:bg-gray-50"
                          >
                            <td class="border px-4 py-2">{{ row.period }}</td>
                            <td class="border px-4 py-2 text-center">
                              {{ row.subscriptions }}
                            </td>
                            <td class="border px-4 py-2 text-center">
                              {{ row.unsubscriptions }}
                            </td>
                            <td class="border px-4 py-2 text-center">
                              {{ row.result }}
                            </td>
                            <!-- 👈 -->
                            <td class="border px-4 py-2 text-center">
                              {{ row.cumulative }}
                            </td>
                          </tr>
                        </tbody>
                      </table>

                      <button
                        v-if="!isGeneratingPDF"
                        @click="exportToExcel2"
                        class="export-btn rounded-[5px] bg-[#FEB72D] py-[7px] px-[10px] mt-2 text-[0.9rem]"
                      >
                        Экспорт таблицы в Excel
                      </button>
                    </div>

                    <a-button
                      v-if="!isGeneratingPDF"
                      type="primary"
                      class="my-4"
                      @click="downloadPDF2"
                    >
                      Скачать PDF отчёт
                    </a-button>
                  </div>
                </section>
              </AccordionItem>

              <AccordionItem title="Продвижение">
                <!-- <p>В разработке</p> -->
                <section class="pt-4">
                  <p>
                    Суть продвижения проекта заключается в создании посадочных
                    страниц (лендингов) ведущих на канал с целью привлечения
                    целевого трафика.
                  </p>
                  <p>
                    Cервис позволяет создовать и привязывать к проекту
                    неограниченное количество таких страниц с интеграцией целей
                    Яндекс Метрики.
                  </p>
                  <p>
                    При грамотной настройке рекламной кампании в Яндекс Директ
                    это способствует снижению стоимости целевых действий
                    (подписок на канал), что делает продвижение экономически
                    оправданным и эффективным для рекламного бюджета.
                  </p>
                  <p>
                    Можно даже просто размещать ссылки на создаваемые лендинги
                    на тематических и бесплатных ресурсах в сети интернет, что
                    уже является хорошим способом решения вопросов в продвижении
                    проекта.
                  </p>
                  <p>
                    <strong
                      >Зачем использовать лендинги, если можно просто разместить
                      ссылку на канал?
                    </strong>
                  </p>
                  <p>
                    Лендинги (посадочные страницы) позволяют учитывать аналитику
                    по целевым действиям (взаимодействиям с лидами —
                    потенциальными клиентами) с целью корректировки тактики
                    продвижения канала, а также содержат тематический контент. С
                    точки зрения органического и релевантного продвижения, они
                    гораздо более эффективны, чем просто ссылка на канал.
                  </p>
                  <p>
                    Преимущество продвижения лендингов через Яндекс Директ
                    заключается в том, что они будут подсовываться
                    (показываться) только целевой аудитории на первых позициях
                    поисковой выдачи и в рекламной сети Яндекса (РСЯ). Это
                    значительно увеличивает вероятность быстрого прироста
                    подписчиков, которым действительно может быть интересен
                    проект (канал) &ndash; целевой аудитории.
                  </p>
                </section>

                <div>
                  <h3
                    class="relative text-[1.1rem] font-semibold text-black pl-4 border-l-8 border-[#45A0F2] bg-white py-3 mb-6 after:content-[''] after:block after:w-full after:h-[2px] after:bg-black after:mt-4 !bg-[#ffffd9] mt-12"
                  >
                    Лендинги (посадочные страницы) на проект
                  </h3>

                  <a-input
                    placeholder="Введите название лендинга для поиска"
                    v-model:value="searchTermLandings"
                    @input="onSearchLandings"
                    style="margin-bottom: 16px"
                    class="rounded mt-5"
                  />

                  <a-table
                    :row-key="(record) => record.id"
                    :data-source="localLandings"
                    :columns="columns_landings"
                    :pagination="false"
                    :row-selection="rowSelection"
                    @change="handleTableChangeLandings"
                    class="w-full overflow-x-auto mt-9 z-0"
                  >
                    <template #bodyCell="{ column, record }">
                      <template v-if="column.key === 'url'">
                        <a
                          :href="`${apiUrl}${record.project_link_clean}-${record.url}`"
                          target="_blank"
                          rel="noopener noreferrer"
                        >
                          {{ apiUrl }}{{ record.project_link_clean }}-{{
                            record.url
                          }}
                        </a>
                      </template>
                    </template>
                  </a-table>

                  <a-pagination
                    :current="pagination_landings.current_page"
                    :pageSize="pagination_landings.per_page"
                    :total="pagination_landings.total"
                    @change="handlePageChangeLandings"
                    @showSizeChange="handlePageSizeChangeLandings"
                    style="margin-top: 20px"
                  />
                </div>
              </AccordionItem>

              <!-- <ul>
              <li v-for="subscriber in subscribers" :key="subscriber.id">
                {{ subscriber.id }} {{ subscriber.first_name }}
              </li>
            </ul> -->
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="py-4" v-else>
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div
          class="w-full bg-[white] overflow-hidden shadow-sm sm:rounded-lg p-4"
        >
          <p class="m-4">
            Доступ закрыт. У вас заблокированны права доступа к вашему аккаунту
            или к данному проекту. Обратитесь к администратору
          </p>
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
:deep(.ant-table-content) {
  min-width: 100% !important;
}
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

.subscribed {
  background: #23b01f;
  padding: 5px 10px;
  border-radius: 5px;
  color: white;
  font-size: 0.7rem;
  width: 100%;
  display: block;
  text-align: center;
}

.unsubscribed {
  background: #ff1e1e;
  padding: 5px 10px;
  border-radius: 5px;
  color: white;
  font-size: 0.7rem;
  width: 100%;
  display: block;
  text-align: center;
}

/* Стили для графика */
canvas {
  display: block;
  margin: 0 auto;
}

table {
  border-collapse: collapse;
  width: 100%;
}
th,
td {
  border: 1px solid #ddd;
  padding: 8px;
}
th {
  background-color: #f4f4f4;
  text-align: left;
}

/** Активность кнопок */
.active-btn {
  border: 0px solid #1677ff !important;
  font-weight: bold;
  box-shadow: 0 0 5px rgba(22, 119, 255, 0.5);
  transition: all 0.3s ease;
}

.inactive-btn {
  opacity: 0.8;
}

@media print {
  .no-print {
    display: none !important;
  }
}
</style>

