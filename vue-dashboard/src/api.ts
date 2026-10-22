import { dashboard, products, warehouses } from "./mock/data";
import type { InventoryApi, ProductListQuery } from "./types";

/**
 * In-memory implementation of the warehouse API endpoints the dashboard uses.
 * Same response envelopes and paging rules as the real API, so swapping in an
 * HTTP client only means implementing InventoryApi with fetch.
 */
export function createMockApi(data = { dashboard, products, warehouses }): InventoryApi {
  return {
    getDashboard: async () => ({ ...data.dashboard }),
    listWarehouses: async () => ({ items: [...data.warehouses] }),
    listProducts: async ({ q, lowStock, limit = 25, offset = 0 }: ProductListQuery = {}) => {
      if (!Number.isInteger(limit) || limit < 1 || limit > 100) throw new RangeError("limit must be 1-100");
      if (!Number.isInteger(offset) || offset < 0) throw new RangeError("offset must be >= 0");
      const needle = q?.trim().toLowerCase();
      const matches = data.products
        .filter((x) => !needle || x.sku.toLowerCase().includes(needle) || x.name.toLowerCase().includes(needle))
        .filter((x) => lowStock === undefined || x.lowStock === lowStock)
        .sort((a, b) => a.sku.localeCompare(b.sku));
      return { items: matches.slice(offset, offset + limit), total: matches.length };
    },
  };
}

export const formatCents = (cents: number): string =>
  new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(cents / 100);
