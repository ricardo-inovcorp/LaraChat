/**
 * Bootstrap JavaScript para LaraChat
 */

import _ from 'lodash';
window._ = _;

/**
 * Importamos o Bootstrap
 */
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

/**
 * Importamos o Axios HTTP library para permitir requisições Ajax
 */
import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// Não configuramos o Pusher aqui, pois ele é importado diretamente na view
// via CDN e configurado diretamente na página que precisa dele

// Inicializar todos os dropdowns ao carregar a página
document.addEventListener('DOMContentLoaded', () => {
    const dropdownElementList = document.querySelectorAll('.dropdown-toggle');
    dropdownElementList.forEach(dropdownToggle => {
        new bootstrap.Dropdown(dropdownToggle);
    });
});
