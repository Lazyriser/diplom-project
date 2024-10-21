<script setup>
import { ref, watch } from "vue";
import { Inertia } from "@inertiajs/inertia";

// Инициализация состояний
const vacancies = ref([]);
const areas = ref([]);
const selectedRegion = ref(1);
const currentPage = ref(1); // Начинаем с 1 страницы
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
  loading.value = true; // Установка состояния загрузки
  try {
    const response = await fetch(
      `/api/vacancies?region=${selectedRegion.value}&page=${currentPage.value}&per_page=${vacanciesPerPage.value}`
    );
    if (!response.ok) {
      throw new Error(`Ошибка: ${response.status}`);
    }
    const data = await response.json();

    // Выводим все вакансии для проверки
    console.log(data.items); // Отладочный вывод

    // Фильтруем вакансии, исключая удалённые
    vacancies.value = data.items.filter((vacancy) => !vacancy.deleted_at);
    totalPages.value = data.pages;

    // Выводим отфильтрованные вакансии для проверки
    console.log(vacancies.value); // Отладочный вывод
  } catch (error) {
    console.error("Ошибка при загрузке вакансий:", error);
  } finally {
    loading.value = false; // Сброс состояния загрузки
  }
};

// Переходы на создание/редактирование вакансии
const createNewJob = () => {
  Inertia.visit("/jobs/create");
};

const editJob = (id) => {
  console.log(`Редактирование вакансии с ID: ${id}`); // Отладочный вывод
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
      await fetchVacancies(); // Обновление списка вакансий после удаления
    } catch (error) {
      console.error("Ошибка при удалении вакансии:", error);
    }
  }
};

// Наблюдатели за изменениями
watch(selectedRegion, () => {
  currentPage.value = 1; // Сброс текущей страницы при смене региона
  fetchVacancies(); // Вызываем обновление вакансий
});

watch(currentPage, fetchVacancies); // При смене страницы вызываем обновление вакансий

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

<template>
  <div class="jobs">
    <!-- Секция выбора региона -->
    <section class="mb-5">
      <div class="container">
        <div class="row">
          <h2>Выберите регион</h2>
        </div>
        <div class="row">
          <select class="form-select" v-model="selectedRegion">
            <option v-for="area in areas" :key="area.id" :value="area.id">
              {{ area.name }}
            </option>
          </select>
        </div>
      </div>
    </section>

    <!-- Секция вакансий -->
    <section>
      <div class="container">
        <div class="row">
          <h2>Вакансии</h2>
          <div v-if="loading">Загрузка...</div>
        </div>
        <div class="row">
          <button @click="createNewJob" class="btn btn-primary">
            Создать новую вакансию
          </button>
          <ul v-if="vacancies.length">
            <li
              v-for="vacancy in vacancies"
              :key="vacancy.id"
              class="p-3 mb-3 border"
            >
              <p>{{ vacancy.name }}</p>
              <button @click.prevent="editJob(vacancy.id)" :disabled="loading">
                Редактировать
              </button>
              <p v-if="vacancy.salary">
                {{ vacancy.salary.from || "Не указано" }} руб. -
                {{ vacancy.salary.to || "Не указано" }} руб.
              </p>
              <p class="small">
                {{ vacancy.employment?.name || "Тип занятости не указан" }}
              </p>
              <a :href="`/jobs/${vacancy.id}`" class="btn btn-primary"
                >Посмотреть</a
              >
              <a :href="vacancy.url" target="_blank" class="btn btn-primary"
                >API</a
              >
            </li>
          </ul>
          <p v-else>Нет вакансий для отображения.</p>
        </div>
      </div>
    </section>

    <!-- Секция пагинации -->
    <section>
      <div class="container">
        <button
          class="btn btn-secondary"
          @click.prevent="prevPage"
          :disabled="currentPage.value === 1"
        >
          Предыдущая
        </button>
        <button
          class="btn btn-secondary"
          @click.prevent="nextPage"
          :disabled="currentPage.value >= totalPages.value"
        >
          Следующая
        </button>
        <p>Страница {{ currentPage }} из {{ totalPages }}</p>
      </div>
    </section>
  </div>
</template>
