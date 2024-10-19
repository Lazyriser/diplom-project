import '../css/app.css';
import './bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css'; // Оставляем подключение стилей Bootstrap
import 'bootstrap'; // Подключение функционала Bootstrap (JS)
import '../css/app.scss'; // Подключаем кастомные стили через SASS

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
	title: (title) => `${title} - ${appName}`,
	resolve: (name) =>
		resolvePageComponent(
			`./Pages/${name}.vue`,
			import.meta.glob('./Pages/**/*.vue'),
		),
	setup({ el, App, props, plugin }) {
		return createApp({ render: () => h(App, props) })
			.use(plugin)
			.use(ZiggyVue)
			.mount(el);
	},
	progress: {
		color: '#4B5563',
	},
});
