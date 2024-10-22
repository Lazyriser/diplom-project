<template>
  <div class="container mt-5">
    <h1>{{ props.job ? "Редактировать вакансию" : "Создать вакансию" }}</h1>
    <form @submit.prevent="submitForm">
      <!-- Название вакансии -->
      <div class="mb-3">
        <label for="title" class="form-label">Название вакансии</label>
        <input
          type="text"
          class="form-control"
          id="title"
          v-model="form.title"
          required
          maxlength="255"
        />
      </div>

      <!-- Описание вакансии -->
      <div class="mb-3">
        <label for="description" class="form-label">Описание</label>
        <textarea
          class="form-control"
          id="description"
          v-model="form.description"
        ></textarea>
      </div>

      <!-- Регион вакансии -->
      <div class="mb-3">
        <label for="region_id" class="form-label">Регион</label>
        <select class="form-select" v-model="form.region_id" required>
          <option v-for="area in areas" :key="area.id" :value="area.id">
            {{ area.name }}
          </option>
        </select>
      </div>

      <!-- Компания -->
      <div class="mb-3">
        <label for="company_name" class="form-label">Компания</label>
        <input
          type="text"
          class="form-control"
          id="company_name"
          v-model="form.company_name"
          maxlength="255"
        />
      </div>

      <!-- Зарплата -->
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="salary_from" class="form-label">Зарплата от</label>
          <input
            type="number"
            class="form-control"
            id="salary_from"
            v-model="form.salary_from"
          />
        </div>
        <div class="col-md-6 mb-3">
          <label for="salary_to" class="form-label">Зарплата до</label>
          <input
            type="number"
            class="form-control"
            id="salary_to"
            v-model="form.salary_to"
            :min="form.salary_from"
          />
        </div>
      </div>

      <!-- Валюта -->
      <div class="mb-3">
        <label for="currency" class="form-label">Валюта</label>
        <input
          type="text"
          class="form-control"
          id="currency"
          v-model="form.currency"
          maxlength="255"
        />
      </div>

      <!-- Тип занятости -->
      <div class="mb-3">
        <label for="employment_type" class="form-label">Тип занятости</label>
        <select class="form-select" v-model="form.employment_type">
          <option value="full-time">Полная занятость</option>
          <option value="part-time">Частичная занятость</option>
          <option value="contract">Контракт</option>
        </select>
      </div>

      <!-- График работы -->
      <div class="mb-3">
        <label for="schedule" class="form-label">График работы</label>
        <input
          type="text"
          class="form-control"
          id="schedule"
          v-model="form.schedule"
          maxlength="255"
        />
      </div>

      <!-- Ключевые навыки -->
      <div class="mb-3">
        <label for="key_skills" class="form-label">Ключевые навыки</label>
        <input
          type="text"
          class="form-control"
          id="key_skills"
          v-model="form.key_skills"
        />
      </div>

      <!-- Опыт работы -->
      <div class="mb-3">
        <label for="experience" class="form-label">Опыт работы</label>
        <input
          type="text"
          class="form-control"
          id="experience"
          v-model="form.experience"
          maxlength="255"
        />
      </div>

      <!-- Адрес -->
      <div class="mb-3">
        <label for="address" class="form-label">Адрес</label>
        <input
          type="text"
          class="form-control"
          id="address"
          v-model="form.address"
          maxlength="255"
        />
      </div>

      <!-- Кнопка отправки -->
      <button type="submit" class="btn btn-primary">
        {{ props.job ? "Обновить вакансию" : "Создать вакансию" }}
      </button>
    </form>

    <!-- Сообщение об ошибке -->
    <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>

    <!-- Сообщение об успехе -->
    <div v-if="successMessage" class="alert alert-success mt-3">
      {{ successMessage }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { Inertia } from "@inertiajs/inertia";

const props = defineProps({
  job: Object,
});

const form = ref({
  api_id: props.job?.api_id || "",
  title: props.job?.title || "",
  description: props.job?.description || "",
  region_id: props.job?.region_id || null,
  company_name: props.job?.company_name || "",
  salary_from: props.job?.salary_from || null,
  salary_to: props.job?.salary_to || null,
  currency: props.job?.currency || "",
  employment_type: props.job?.employment_type || "",
  schedule: props.job?.schedule || "",
  key_skills: props.job?.key_skills || "",
  experience: props.job?.experience || "",
  address: props.job?.address || "",
});

const areas = ref([]);
const error = ref(null);
const successMessage = ref(null);

const fetchAreas = async () => {
  try {
    const response = await fetch("/api/areas");
    if (!response.ok) {
      throw new Error(`Ошибка при загрузке регионов: ${response.status}`);
    }
    const data = await response.json();
    areas.value = data;
  } catch (err) {
    error.value = "Не удалось загрузить регионы.";
  }
};

onMounted(fetchAreas);

const submitForm = async () => {
  error.value = null;
  successMessage.value = null;

  try {
    const url = props.job ? `/api/jobs/${props.job.id}` : "/api/jobs";
    const method = props.job ? "put" : "post";

    await Inertia[method](url, form.value, {
      onError: (errors) => {
        console.error(errors); // Логирование ошибок
        if (errors && errors.errors) {
          error.value = Object.values(errors.errors).flat().join(", "); // Объединяем все сообщения об ошибках
        } else {
          error.value = errors.message || "Ошибка при отправке данных.";
        }
      },
      onSuccess: () => {
        successMessage.value = "Вакансия успешно сохранена!";
        setTimeout(() => {
          successMessage.value = null; // Скрыть сообщение об успехе через 3 секунды
        }, 3000);
      },
    });
  } catch (err) {
    console.error("Ошибка при отправке данных:", err);
    error.value = "Произошла ошибка при отправке данных.";
  }
};
</script>
