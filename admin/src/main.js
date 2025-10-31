import { createApp, reactive } from 'vue';
import App from './App.vue';
import './styles.css';
import { createApi } from './lib/api.js';

try {
  console.info('[MR_ML] main.js start', window.MR_ML_BOOT);
  const boot = window.MR_ML_BOOT || {};

  const app = createApp(App);
  app.provide('boot', reactive({
    rootUrl: boot.rootUrl || '',
    adminUrl: boot.adminUrl || '',
    restUrl: boot.restUrl || '',
    nonce: boot.nonce || '',
    userCanManage: !!boot.userCanManage,
    pluginVersion: boot.pluginVersion || '0.0.0',
  }));

  const api = createApi(boot);
  app.provide('api', api);

  app.mount('#mr-ml-app');
  window.__MR_ML_MOUNTED__ = true;
  console.info('[MR_ML] app mounted');
} catch (e) {
  console.error('[MR_ML] mount error', e);
}


