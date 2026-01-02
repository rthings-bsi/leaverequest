import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Echo debug listeners (logs incoming realtime events)
import './echo-listeners';
