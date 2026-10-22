<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { formatCents } from "./api";
import type { Dashboard, InventoryApi, Product, Warehouse } from "./types";

const props = defineProps<{ api: InventoryApi; pageSize?: number }>();
const limit = computed(() => props.pageSize ?? 5);

const dashboard = ref<Dashboard>();
const warehouses = ref<Warehouse[]>([]);
const products = ref<Product[]>([]);
const total = ref(0);
const offset = ref(0);
const q = ref("");
const lowStockOnly = ref(false);
const error = ref("");

const cards = computed(() =>
  dashboard.value
    ? [
        { label: "Active products", value: dashboard.value.products },
        { label: "Low stock", value: dashboard.value.lowStock },
        { label: "Open purchase orders", value: dashboard.value.openPurchaseOrders },
        { label: "Transfers in transit", value: dashboard.value.transfersInTransit },
        { label: "Movements (7 days)", value: dashboard.value.movementsLast7Days },
      ]
    : [],
);
const pageLabel = computed(() =>
  total.value === 0
    ? "No products"
    : `${offset.value + 1}–${Math.min(offset.value + limit.value, total.value)} of ${total.value}`,
);

const message = (e: unknown, fallback: string) => (e instanceof Error ? e.message : fallback);

async function loadProducts() {
  try {
    const page = await props.api.listProducts({
      q: q.value || undefined,
      lowStock: lowStockOnly.value || undefined,
      limit: limit.value,
      offset: offset.value,
    });
    products.value = page.items;
    total.value = page.total;
  } catch (e) {
    error.value = message(e, "Could not load products");
  }
}

watch([q, lowStockOnly], () => {
  if (offset.value === 0) void loadProducts();
  else offset.value = 0; // the offset watcher reloads
});
watch(offset, () => void loadProducts());

onMounted(async () => {
  try {
    const [d, w] = await Promise.all([props.api.getDashboard(), props.api.listWarehouses()]);
    dashboard.value = d;
    warehouses.value = w.items;
  } catch (e) {
    error.value = message(e, "Could not load dashboard");
  }
  await loadProducts();
});
</script>

<template>
  <main>
    <h1>Inventory dashboard</h1>
    <p class="muted">
      {{ warehouses.length }} warehouses:
      {{ warehouses.map((w) => `${w.code} (${w.city})`).join(", ") }} · fictional data
    </p>

    <p
      v-if="error"
      role="alert"
      class="error"
    >
      {{ error }}
    </p>

    <section
      class="cards"
      aria-label="Headline counts"
    >
      <div
        v-for="card in cards"
        :key="card.label"
        class="card"
      >
        <span class="value">{{ card.value }}</span>
        <span class="label">{{ card.label }}</span>
      </div>
    </section>

    <section aria-label="Products">
      <div class="filters">
        <label>
          Search
          <input
            v-model="q"
            type="search"
            placeholder="SKU or name"
          >
        </label>
        <label>
          <input
            v-model="lowStockOnly"
            type="checkbox"
          >
          Low stock only
        </label>
      </div>

      <table>
        <thead>
          <tr>
            <th>SKU</th>
            <th>Name</th>
            <th class="num">
              On hand
            </th>
            <th class="num">
              Reorder at
            </th>
            <th class="num">
              Unit cost
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="product in products"
            :key="product.id"
            :class="{ low: product.lowStock, inactive: !product.active }"
          >
            <td>{{ product.sku }}</td>
            <td>
              {{ product.name }}
              <span
                v-if="product.lowStock"
                class="badge"
              >low</span>
              <span
                v-if="!product.active"
                class="badge muted"
              >inactive</span>
            </td>
            <td class="num">
              {{ product.totalQuantity }} {{ product.unit }}
            </td>
            <td class="num">
              {{ product.reorderPoint }}
            </td>
            <td class="num">
              {{ formatCents(product.unitCostCents) }}
            </td>
          </tr>
        </tbody>
      </table>

      <nav class="pager">
        <button
          :disabled="offset === 0"
          @click="offset = Math.max(0, offset - limit)"
        >
          Previous
        </button>
        <span data-test="page">{{ pageLabel }}</span>
        <button
          :disabled="offset + limit >= total"
          @click="offset += limit"
        >
          Next
        </button>
      </nav>
    </section>
  </main>
</template>
