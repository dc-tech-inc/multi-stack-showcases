import { createApp } from "vue";
import App from "./App.vue";
import { createMockApi } from "./api";
import "./style.css";

createApp(App, { api: createMockApi() }).mount("#app");
