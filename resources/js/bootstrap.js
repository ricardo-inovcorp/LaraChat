/**
 * Bootstrap JavaScript para LaraChat
 */

import _ from 'lodash';
window._ = _;

/**
 * Importamos o Axios HTTP library para permitir requisições Ajax
 */
import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// Não configuramos o Pusher aqui, pois ele é importado diretamente na view
// via CDN e configurado diretamente na página que precisa dele
