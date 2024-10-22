<template>
  <section>
    <div class="container my-5">
      <h1 class="mb-4">{{ job.title }}</h1>
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Информация о вакансии</h5>
          <p class="card-text" v-if="job.description">
            <strong>Описание:</strong>
            <span v-html="job.description"></span>
          </p>
          <p class="card-text" v-if="job.company_name">
            <strong>Компания:</strong> {{ job.company_name }}
          </p>
          <p class="card-text" v-if="job.experience">
            <strong>Опыт работы:</strong> {{ job.experience }}
          </p>
          <p class="card-text" v-if="job.salary_from || job.salary_to">
            <strong>Зарплата:</strong>
            {{ job.salary_from }} - {{ job.salary_to }} {{ job.currency }}
          </p>
          <p class="card-text" v-if="job.employment_type">
            <strong>Тип занятости:</strong> {{ job.employment_type }}
          </p>
          <p class="card-text" v-if="job.schedule">
            <strong>График работы:</strong> {{ job.schedule }}
          </p>
          <p class="card-text" v-if="job.key_skills">
            <strong>Ключевые навыки:</strong> {{ job.key_skills }}
          </p>
          <p class="card-text" v-if="job.address">
            <strong>Адрес:</strong> {{ job.address }}
          </p>
        </div>
      </div>
      <!-- Кнопка удаления вакансии -->
      <!-- <div class="mt-4">
        <button @click="deleteJob(job.api_id)" class="btn btn-danger">Удалить вакансию</button>
      </div> -->
      <!-- Кнопка возврата на список вакансий -->
      <div class="mt-4">
        <a href="/jobs" class="btn btn-primary">Вернуться к списку вакансий</a>
      </div>
    </div>
  </section>
</template>

<script>
import axios from 'axios';

export default {
  props: {
    job: Object, // Ожидаем объект вакансии
  },
  methods: {
    async deleteJob(apiId) {
      try {
        await axios.delete(`/api/jobs/${apiId}`);
        // Перенаправляем пользователя обратно к списку вакансий
        this.$router.push('/jobs');
      } catch (error) {
        console.error('Ошибка при удалении вакансии:', error);
        alert('Не удалось удалить вакансию. Попробуйте еще раз.');
      }
    },
  },
};
</script>

<style scoped>
.card {
  border: 1px solid #007bff; /* Синяя рамка для карточки */
}
.card-title {
  color: #007bff; /* Цвет заголовка карточки */
}
</style>
