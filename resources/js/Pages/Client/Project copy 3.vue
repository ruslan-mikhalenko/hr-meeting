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

const showGraph = ref(false);
const groupBy = ref(null);

const chartData = ref(props.chartData || []);
const dateRange = ref([props.defaultStart, props.defaultEnd]);
const grouping = ref(props.defaultGrouping || "day");
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

  const worksheet = XLSX.utils.json_to_sheet(data);
  const workbook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(workbook, worksheet, "Подписчики");
  XLSX.writeFile(workbook, "chart_data.xlsx");
}

function exportToExcel2() {
  const data = tableData.value.map((row) => ({
    Период: row.period,
    Подписались: row.subscriptions,
    Отписались: row.unsubscriptions,
    Накопление: row.cumulative,
  }));

  // Добавим итоговую строку "Итого"
  const totalSubscriptions = data.reduce(
    (sum, row) => sum + row["Подписались"],
    0
  );
  const totalUnsubscriptions = data.reduce(
    (sum, row) => sum + row["Отписались"],
    0
  );
  const totalCumulative = data.length ? data[data.length - 1]["Накопление"] : 0;

  data.push({
    Период: "Итого",
    Подписались: totalSubscriptions,
    Отписались: totalUnsubscriptions,
    Накопление: totalCumulative,
  });

  const worksheet = XLSX.utils.json_to_sheet(data);
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

  groupBy.value = "day"; // <- Устанавливаем группировку по умолчанию
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
  return isActiveFilter.value === 1
    ? "Активные подписчики"
    : "Отписавшиеся пользователи";
});

const label_user = computed(() => {
  return isActiveFilter.value === 1 ? "Подписчики" : "Отписчики";
});

const onDateChange = (dates) => {
  if (!dates || dates.length === 0) {
    // Если диапазон очищен
    groupBy.value = "day"; // Сброс группировки на 'day'
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
  const cumulativeData = cumulativeChartData.value.map((item) => item.count);

  cumulativeChartInstance = new Chart(ctx, {
    type: "line",
    data: {
      labels,
      datasets: [
        {
          label: "Накопленные подписчики",
          data: cumulativeData,
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
          beginAtZero: true,
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
          <div
            class="w-full bg-[#7B869B] text-[white] font-bold overflow-hidden shadow-sm sm:rounded-lg p-4"
          >
            <!-- Контент блока -->
            Проект: {{ project.name }}
          </div>
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
              <p><b>ID проекта:</b>&nbsp; {{ project.link }}</p>
              <p>
                <b>Ссылка на проект:</b>&nbsp;
                {{ project.username ?? "Отсутствует" }}
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
                  project.participants_count
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
              <div class="flex justify-center my-4">
                <a-space>
                  <a-button
                    type="primary"
                    :type="isActiveFilter === 1 ? 'primary' : 'default'"
                    @click="switchActiveFilter(1)"
                  >
                    Подписка
                  </a-button>
                  <a-button
                    :type="isActiveFilter === 0 ? 'primary' : 'default'"
                    danger
                    @click="switchActiveFilter(0)"
                  >
                    Отписка
                  </a-button>
                </a-space>
              </div>
              <!-- Заголовок динамический -->
              <h1>{{ label }}</h1>
              <h2>{{ total }}</h2>
              <div class="mt-6">
                <!-- Выбор даты и интервала -->
                <div
                  class="flex flex-col lg:flex-row items-center justify-center gap-4 mb-4"
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

                <!-- График -->
                <canvas
                  ref="chartRef"
                  style="max-width: 100%; height: 400px"
                  v-if="showGraph"
                ></canvas>
              </div>

              <div>
                <button @click="exportToExcel" class="export-btn">
                  Экспорт в Excel
                </button>

                <table v-if="filteredData.length">
                  <thead>
                    <tr>
                      <th>Период</th>
                      <th>Количество</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in filteredData" :key="item.period">
                      <td>{{ item.period }}</td>
                      <td>{{ item.count }}</td>
                    </tr>
                    <tr>
                      <td><strong>Итого</strong></td>
                      <td>
                        <strong>{{ total }}</strong>
                      </td>
                    </tr>
                  </tbody>
                </table>

                <p v-else>Нет данных для отображения.</p>
              </div>

              <div class="mt-10">
                <h2 class="text-lg font-semibold text-center mb-4">
                  График накопления подписчиков
                </h2>
                <canvas
                  ref="cumulativeChartRef"
                  style="max-width: 100%; height: 400px"
                ></canvas>
              </div>

              <div class="p-4">
                <h2 class="text-xl font-semibold mb-4">Подписки по периодам</h2>
                <button @click="exportToExcel2" class="export-btn">
                  Экспорт в Excel
                </button>
                <table
                  class="min-w-full table-auto border-collapse border border-gray-300"
                >
                  <thead class="bg-gray-100">
                    <tr>
                      <th class="border px-4 py-2 text-left">Период</th>
                      <th class="border px-4 py-2 text-left">Подписались</th>
                      <th class="border px-4 py-2 text-left">Отписались</th>
                      <th class="border px-4 py-2 text-left">Накопление</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="(row, index) in tableData"
                      :key="index"
                      class="hover:bg-gray-50"
                    >
                      <td class="border px-4 py-2">{{ row.period }}</td>
                      <td class="border px-4 py-2">{{ row.subscriptions }}</td>
                      <td class="border px-4 py-2">
                        {{ row.unsubscriptions }}
                      </td>
                      <td class="border px-4 py-2">{{ row.cumulative }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <h2>{{ label_user }}</h2>

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
                    <span v-if="record.is_active == 1" class="subscribed"
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
</style>

