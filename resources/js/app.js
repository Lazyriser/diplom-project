import '../css/app.css'; // Подключаем стили из app.css
import './bootstrap'; // Подключаем настройки bootstrap
import 'bootstrap/dist/css/bootstrap.min.css'; // Подключаем стили Bootstrap
import 'bootstrap'; // Подключаем функционал Bootstrap (JS)
import '../css/app.scss'; // Подключаем кастомные стили через SASS
import axios from 'axios'; // Импортируем библиотеку axios для HTTP-запросов

// Получаем CSRF-токен из мета-тега
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Устанавливаем CSRF-токен в заголовки по умолчанию для Axios
axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;

import { createInertiaApp } from '@inertiajs/vue3'; // Импортируем функцию для создания Inertia приложения
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'; // Импортируем вспомогательную функцию для резолвинга компонентов
import { createApp, h } from 'vue'; // Импортируем функции из Vue для создания приложения
import { ZiggyVue } from '../../vendor/tightenco/ziggy'; // Импортируем ZiggyVue для маршрутизации

const appName = import.meta.env.VITE_APP_NAME || 'Laravel'; // Получаем имя приложения из переменных окружения

createInertiaApp({
	title: (title) => `${title} - ${appName}`, // Устанавливаем заголовок окна браузера
	resolve: (name) =>
		resolvePageComponent(
			`./Pages/${name}.vue`, // Путь до компонента страницы
			import.meta.glob('./Pages/**/*.vue'), // Глобальная импорт всех .vue файлов в папке Pages
		),
	setup({ el, App, props, plugin }) { // Настраиваем приложение
		return createApp({ render: () => h(App, props) }) // Создаем Vue-приложение и рендерим App
			.use(plugin) // Используем переданный плагин
			.use(ZiggyVue) // Используем ZiggyVue
			.mount(el); // Монтируем приложение в элемент el
	},
	progress: {
		color: '#4B5563', // Устанавливаем цвет индикатора загрузки
	},
});

