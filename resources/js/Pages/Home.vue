<script>
export default {
  props: {
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
  },
};
</script>

<template>
  <div class="container">
    <h1>Добро пожаловать</h1>
    <p v-if="canLogin">Вы можете войти на сайт</p>
    <p v-if="canRegister">Вы можете зарегистрироваться</p>
    <br />
    <p>Sitemap:</p>
    <ul>
      <li><a href="/jobs">Вакансии</a></li>
			<li><a href="/dashboard">Дашбоард</a></li>
    </ul>
		<p>API:</p>
		<ul>
			<li><a href="/api/vacancies">Вакансии</a></li>
			<li><a href="/api/areas">Регионы</a></li>
		</ul>
		<br>
    <p>Версия Laravel: {{ laravelVersion }}</p>
    <p>Версия PHP: {{ phpVersion }}</p>
  </div>
</template>
