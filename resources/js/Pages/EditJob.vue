<template>
  <section>
    <div class="container my-5">
      <h1>Редактирование вакансии</h1>
      <form @submit.prevent="updateJob">
        <div class="mb-3">
          <label for="title" class="form-label">Название вакансии</label>
          <input
            type="text"
            id="title"
            class="form-control"
            v-model="form.title"
            required
          />
        </div>
        <div class="mb-3">
          <label for="description" class="form-label">Описание</label>
          <textarea
            id="description"
            class="form-control"
            v-model="form.description"
            required
          ></textarea>
        </div>
        <div class="mb-3">
          <label for="region_id" class="form-label">Регион</label>
          <select
            id="region_id"
            class="form-select"
            v-model="form.region_id"
            required
          >
            <option disabled value="">Выберите регион</option>
            <option v-for="region in regions" :key="region.id" :value="region.id">
              {{ region.name }}
            </option>
          </select>
        </div>
        <div class="mb-3">
          <label for="company_name" class="form-label">Компания</label>
          <input
            type="text"
            id="company_name"
            class="form-control"
            v-model="form.company_name"
          />
        </div>
        <div class="mb-3">
          <label for="salary_from" class="form-label">Зарплата от</label>
          <input
            type="number"
            id="salary_from"
            class="form-control"
            v-model="form.salary_from"
          />
        </div>
        <div class="mb-3">
          <label for="salary_to" class="form-label">Зарплата до</label>
          <input
            type="number"
            id="salary_to"
            class="form-control"
            v-model="form.salary_to"
          />
        </div>
        <div class="mb-3">
          <label for="currency" class="form-label">Валюта</label>
          <input
            type="text"
            id="currency"
            class="form-control"
            v-model="form.currency"
          />
        </div>
        <div class="mb-3">
          <label for="employment_type" class="form-label">Тип занятости</label>
          <select
            id="employment_type"
            class="form-select"
            v-model="form.employment_type"
          >
            <option disabled value="">Выберите тип занятости</option>
            <option value="full-time">Полная занятость</option>
            <option value="part-time">Частичная занятость</option>
            <option value="contract">Контракт</option>
          </select>
        </div>
        <div class="mb-3">
          <label for="schedule" class="form-label">График работы</label>
          <input
            type="text"
            id="schedule"
            class="form-control"
            v-model="form.schedule"
          />
        </div>
        <div class="mb-3">
          <label for="key_skills" class="form-label">Ключевые навыки</label>
          <input
            type="text"
            id="key_skills"
            class="form-control"
            v-model="form.key_skills"
          />
        </div>
        <div class="mb-3">
          <label for="experience" class="form-label">Опыт работы</label>
          <input
            type="text"
            id="experience"
            class="form-control"
            v-model="form.experience"
          />
        </div>
        <div class="mb-3">
          <label for="address" class="form-label">Адрес</label>
          <input
            type="text"
            id="address"
            class="form-control"
            v-model="form.address"
          />
        </div>
        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
      </form>
      <!-- Кнопка удаления вакансии -->
      <div class="mt-4">
        <button @click="deleteJob" class="btn btn-danger">Удалить вакансию</button>
      </div>
    </div>
  </section>
</template>

<script>
import axios from 'axios';

export default {
  props: {
    job: Object, // Получаем объект вакансии
    regions: Array, // Получаем список регионов
  },
  data() {
    return {
      form: {
        title: this.job.title,
        description: this.job.description,
        region_id: this.job.region_id,
        company_name: this.job.company_name,
        salary_from: this.job.salary_from,
        salary_to: this.job.salary_to,
        currency: this.job.currency,
        employment_type: this.job.employment_type,
        schedule: this.job.schedule,
        key_skills: this.job.key_skills,
        experience: this.job.experience,
        address: this.job.address,
      },
    };
  },
  methods: {
    async updateJob() {
      try {
        await this.$inertia.put(`/jobs/${this.job.api_id}`, this.form);
        this.$inertia.visit('/jobs'); // Переход к списку вакансий после успешного обновления
      } catch (error) {
        console.error(error);
      }
    },
    async deleteJob() {
      if (confirm('Вы уверены, что хотите удалить эту вакансию?')) {
        try {
          await axios.delete(`/api/jobs/${this.job.api_id}`);
          this.$inertia.visit('/jobs'); // Переход к списку вакансий после удаления
        } catch (error) {
          console.error('Ошибка при удалении вакансии:', error);
          alert('Не удалось удалить вакансию. Попробуйте еще раз.');
        }
      }
    },
  },
};
</script>

<style scoped>
/* Добавьте свои стили, если нужно */
</style>
