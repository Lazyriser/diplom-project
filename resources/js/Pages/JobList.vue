<template>
  <div class="jobs py-5">
    <!-- Навигационная панель с кнопками входа и регистрации -->
    <nav class="navbar navbar-light bg-light mb-5">
      <div class="container">
        <a class="navbar-brand" href="/">Diplom</a>
        <div class="d-flex">
          <a href="/login" class="btn btn-outline-primary me-2">Вход</a>
          <a href="/register" class="btn btn-outline-success">Регистрация</a>
        </div>
      </div>
    </nav>

    <!-- Секция выбора региона -->
    <section class="mb-5">
      <div class="container">
        <div class="row mb-3">
          <h2 class="col text-center">Выберите регион</h2>
        </div>
        <div class="row justify-content-center">
          <div class="col-md-6">
            <select class="form-select" v-model="selectedRegion">
              <option v-for="area in areas" :key="area.id" :value="area.id">
                {{ area.name }}
              </option>
            </select>
          </div>
        </div>
      </div>
    </section>

    <!-- Секция вакансий -->
    <section class="mb-5">
      <div class="container">
        <div class="row mb-3">
          <h2 class="col text-center">Вакансии</h2>
        </div>
        <div class="row justify-content-center">
          <div v-if="loading" class="text-center">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Загрузка...</span>
            </div>
          </div>
          <div v-else>
            <button @click="createNewJob" class="btn btn-primary mb-3">
              Создать новую вакансию
            </button>
            <ul v-if="vacancies.length" class="list-group">
              <li
                v-for="vacancy in vacancies"
                :key="vacancy.api_id"
                class="list-group-item mb-3 p-4"
              >
                <div class="row">
                  <div class="col-md-9">
                    <h5>{{ vacancy.title }}</h5>
                    <p v-if="vacancy.salary">
                      Зарплата: {{ vacancy.salary.from || "Не указано" }} руб. -
                      {{ vacancy.salary.to || "Не указано" }} руб.
                    </p>
                    <p class="small">
                      {{
                        vacancy.employment?.name || "Тип занятости не указан"
                      }}
                    </p>
                    <p v-html="truncateDescription(vacancy.description)"></p>
                  </div>
                  <div class="col-md-3 text-end">
                    <button
                      @click.prevent="editJob(vacancy.api_id)"
                      class="btn btn-warning mb-2"
                      :disabled="loading"
                    >
                      Редактировать
                    </button>
                    <button
                      @click.prevent="deleteJob(vacancy.api_id)"
                      class="btn btn-danger mb-2"
                      :disabled="loading"
                    >
                      Удалить
                    </button>
                    <a
                      :href="`/jobs/${vacancy.api_id}`"
                      class="btn btn-info mb-2"
                      >Посмотреть</a
                    >
                    <a
                      :href="vacancy.url"
                      target="_blank"
                      class="btn btn-secondary"
                      >API</a
                    >
                  </div>
                </div>
              </li>
            </ul>
            <p v-else class="text-center">Нет вакансий для отображения.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Секция пагинации -->
    <section class="pagination-section">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-md-6 text-center">
            <button
              class="btn btn-outline-secondary me-2"
              @click.prevent="prevPage"
              :disabled="currentPage.value === 1"
            >
              Предыдущая
            </button>
            <button
              class="btn btn-outline-secondary"
              @click.prevent="nextPage"
              :disabled="currentPage.value >= totalPages.value"
            >
              Следующая
            </button>
            <p class="mt-3">Страница {{ currentPage }} из {{ totalPages }}</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";
import { Inertia } from "@inertiajs/inertia";

// Инициализация состояний
const vacancies = ref([]);
const areas = ref([]);
const selectedRegion = ref(1);
const currentPage = ref(1);
const totalPages = ref(0);
const vacanciesPerPage = ref(5);
const loading = ref(false);

// Получение регионов
const fetchAreas = async () => {
  try {
    const response = await fetch("/api/areas");
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    const data = await response.json();
    areas.value = data;
  } catch (error) {
    console.error("Ошибка при загрузке регионов:", error);
  }
};

// Получение вакансий
const fetchVacancies = async () => {
  loading.value = true;
  try {
    const response = await fetch(
      `/api/vacancies?region=${selectedRegion.value}&page=${currentPage.value}&per_page=${vacanciesPerPage.value}`
    );
    if (!response.ok) {
      throw new Error(`Ошибка: ${response.status}`);
    }
    const data = await response.json();

    // Логируем полный ответ
    console.log("Полученные данные от API:", data);

    // Проверяем, существует ли data.data
    if (!data.data) {
      console.error("data не найдены в ответе API");
      vacancies.value = []; // Устанавливаем пустой массив, если data нет
      totalPages.value = 0; // Обнуляем количество страниц
      return;
    }

    // Фильтруем вакансии
    vacancies.value = data.data.filter((vacancy) => !vacancy.deleted_at);

    // Устанавливаем количество страниц
    totalPages.value = data.last_page || 0; // Убедитесь, что last_page существует
  } catch (error) {
    console.error("Ошибка при загрузке вакансий:", error);
  } finally {
    loading.value = false;
  }
};

// Переходы на создание/редактирование вакансии
const createNewJob = () => {
  Inertia.visit("/jobs/create");
};

const editJob = (id) => {
  Inertia.visit(`/jobs/${id}/edit`);
};

// Удаление вакансии
const deleteJob = async (id) => {
  if (confirm("Вы уверены, что хотите удалить эту вакансию?")) {
    try {
      const response = await fetch(`/api/jobs/${id}`, {
        method: "DELETE",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content"),
        },
      });

      if (!response.ok) {
        throw new Error("Ошибка при удалении вакансии");
      }

      vacancies.value = vacancies.value.filter(
        (vacancy) => vacancy.api_id !== id
      );
    } catch (error) {
      console.error("Ошибка при удалении вакансии:", error);
    }
  }
};

const truncateDescription = (description) => {
  if (!description) return "Описание не указано";

  // Убираем HTML-теги
  const strippedDescription = description.replace(/<[^>]*>/g, "");

  // Сокращаем до 150 символов и добавляем многоточие, если нужно
  return strippedDescription.length > 150
    ? strippedDescription.substring(0, 150) + "..."
    : strippedDescription;
};

// Наблюдатели за изменениями
watch(selectedRegion, () => {
  currentPage.value = 1;
  fetchVacancies();
});

watch(currentPage, fetchVacancies);

// Первоначальная загрузка данных
fetchAreas();
fetchVacancies();

// Пагинация
const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value -= 1;
  }
};

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value += 1;
  }
};
</script>

<style scoped>
.jobs {
  background-color: #f9f9f9;
}

.list-group-item {
  background-color: white;
  border-radius: 5px;
  box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
}

.pagination-section {
  background-color: #e9ecef;
  padding: 20px 0;
}

.btn {
  min-width: 120px;
}
</style>
