<template>
  <div>
    <h1>{{ props.job ? 'Редактировать вакансию' : 'Создать вакансию' }}</h1>
    <form @submit.prevent="updateJob"> <!-- @submit.prevent предотвращает перезагрузку страницы -->
      <!-- Название вакансии -->
      <div>
        <label for="title">Название:</label>
        <input type="text" v-model="form.title" required />
      </div>
      <!-- Описание вакансии -->
      <div>
        <label for="description">Описание:</label>
        <textarea v-model="form.description" required></textarea>
      </div>
      <!-- Регион вакансии -->
      <div>
        <label for="region_id">Регион:</label>
        <select v-model="form.region_id" required>
          <option v-for="area in areas" :key="area.id" :value="area.id">
            {{ area.name }}
          </option>
        </select>
      </div>
      <!-- Кнопка сохранения -->
      <button type="submit">Сохранить изменения</button>
    </form>

    <!-- Сообщение об ошибке, если есть -->
    <div v-if="error" class="error">{{ error }}</div>

    <!-- Сообщение об успешном обновлении -->
    <div v-if="successMessage" class="success">{{ successMessage }}</div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { Inertia } from "@inertiajs/inertia"; // Импортируем Inertia

const props = defineProps({
  job: Object, // Получаем данные о вакансии
});

// Инициализация формы с данными вакансии или пустыми значениями для создания новой вакансии
const form = ref({
  title: props.job?.title || "", // Используем оператор безопасной навигации
  description: props.job?.description || "",
  region_id: props.job?.region_id || null,
});

const areas = ref([]); // Массив для регионов
const error = ref(null); // Состояние для ошибок
const successMessage = ref(null); // Состояние для успешного сообщения

// Функция загрузки регионов
const fetchAreas = async () => {
  try {
    const response = await fetch("/api/areas");
    if (!response.ok) {
      throw new Error(`Ошибка при загрузке регионов: ${response.status}`);
    }
    const data = await response.json();
    areas.value = data;
  } catch (err) {
    console.error(err);
    error.value = "Не удалось загрузить регионы."; // Установка ошибки
  }
};

// Вызов загрузки регионов при монтировании компонента
onMounted(fetchAreas);

// Функция обновления вакансии
const updateJob = async () => {
  // Сброс ошибок и успешного сообщения перед отправкой
  error.value = null;
  successMessage.value = null;

  try {
    const url = props.job ? `/api/jobs/${props.job.id}` : '/api/jobs'; // Используем URL для обновления или создания вакансии
    const method = props.job ? 'put' : 'post'; // Определяем метод запроса

    await Inertia[method](url, form.value, {
      onError: (errors) => {
        // Обработка ошибок валидации с сервера
        error.value = errors.message || "Ошибка при обновлении вакансии.";
      },
      onSuccess: () => {
        error.value = null; // Очистка ошибок после успешного обновления
        successMessage.value = "Вакансия успешно обновлена!"; // Установка сообщения об успехе
      },
    });
  } catch (err) {
    console.error(err);
    error.value = "Не удалось обновить вакансию."; // Обработка ошибки запроса
  }
};
</script>

<style>
.error {
  color: red; /* Стили для отображения ошибок */
}
.success {
  color: green; /* Стили для отображения успешного сообщения */
}
</style>
