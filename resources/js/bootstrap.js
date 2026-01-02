import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Optional: Laravel Echo + Pusher setup for realtime broadcasting.
// Install with: `npm install --save laravel-echo pusher-js`
// try {
// 	import Pusher from 'pusher-js';
// 	import Echo from 'laravel-echo';

// 	window.Pusher = Pusher;

// 	window.Echo = new Echo({
// 		broadcaster: 'pusher',
// 		key: process.env.MIX_PUSHER_APP_KEY || process.env.VITE_PUSHER_APP_KEY || 'local',
// 		cluster: process.env.MIX_PUSHER_APP_CLUSTER || process.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
// 		wsHost: process.env.MIX_PUSHER_HOST || process.env.VITE_PUSHER_HOST || window.location.hostname,
// 		wsPort: process.env.MIX_PUSHER_PORT || process.env.VITE_PUSHER_PORT || 6001,
// 		forceTLS: (process.env.MIX_PUSHER_SCHEME || process.env.VITE_PUSHER_SCHEME || 'ws') === 'wss',
// 		encrypted: false,
// 		enabledTransports: ['ws', 'wss'],
// 	});
// } catch (e) {
// 	// Echo/Pusher not installed or failed to load — continue without realtime.
// }
