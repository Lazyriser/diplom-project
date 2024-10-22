<template>
  <header>
    <meta name="csrf-token" :content="csrfToken" />
    <nav class="navbar navbar-light bg-light mb-5">
      <div class="container">
        <a class="navbar-brand" href="/">Diplom</a>
        <div class="d-flex">
          <a href="/login" class="btn btn-outline-primary me-2">Вход</a>
          <a href="/register" class="btn btn-outline-success">Регистрация</a>
        </div>
      </div>
    </nav>
  </header>
</template>

<script setup>
import { ref } from 'vue';

// Получаем CSRF токен
const csrfToken = ref(document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
</script>

<style scoped>
/* Ваши стили для шапки */
</style>
