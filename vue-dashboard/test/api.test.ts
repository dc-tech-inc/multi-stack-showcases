import { describe, expect, it } from "vitest";
import { createMockApi, formatCents } from "../src/api";
import { products } from "../src/mock/data";

const api = createMockApi();

describe("mock inventory API", () => {
  it("returns every product sorted by SKU with the total", async () => {
    const page = await api.listProducts({ limit: 100 });
    const skus = page.items.map((p) => p.sku);
    expect(page.total).toBe(products.length);
    expect(skus).toEqual([...skus].sort());
  });

  it("defaults to 25 per page like the real API", async () => {
    const many = Array.from({ length: 30 }, (_, i) => ({ ...products[0]!, id: String(i), sku: `SKU-${String(i).padStart(2, "0")}` }));
    const page = await createMockApi({ dashboard: await api.getDashboard(), products: many, warehouses: [] }).listProducts();
    expect(page.items).toHaveLength(25);
    expect(page.total).toBe(30);
  });

  it("pages with limit and offset", async () => {
    const all = await api.listProducts({ limit: 100 });
    const second = await api.listProducts({ limit: 3, offset: 3 });
    expect(second.items).toEqual(all.items.slice(3, 6));
    expect(second.total).toBe(all.total);
  });

  it("searches SKU and name case-insensitively", async () => {
    expect((await api.listProducts({ q: "box" })).items.map((p) => p.sku)).toEqual(["BOX-LG", "BOX-SM"]);
    expect((await api.listProducts({ q: "  Scanner " })).items.map((p) => p.sku)).toEqual(["SCN-01"]);
  });

  it("filters low stock, which never includes inactive products", async () => {
    const low = await api.listProducts({ lowStock: true, limit: 100 });
    expect(low.items.length).toBeGreaterThan(0);
    for (const p of low.items) {
      expect(p.active).toBe(true);
      expect(p.totalQuantity).toBeLessThanOrEqual(p.reorderPoint);
    }
    const notLow = await api.listProducts({ lowStock: false, limit: 100 });
    expect(low.total + notLow.total).toBe(products.length);
  });

  it("rejects paging outside the API's bounds", async () => {
    await expect(api.listProducts({ limit: 0 })).rejects.toThrow(RangeError);
    await expect(api.listProducts({ limit: 101 })).rejects.toThrow(RangeError);
    await expect(api.listProducts({ offset: -1 })).rejects.toThrow(RangeError);
  });

  it("keeps the dashboard counts consistent with the products", async () => {
    const d = await api.getDashboard();
    expect(d.products).toBe(products.filter((p) => p.active).length);
    expect(d.lowStock).toBe((await api.listProducts({ lowStock: true })).total);
  });

  it("lists warehouses", async () => {
    expect((await api.listWarehouses()).items.map((w) => w.code)).toEqual(["CLT-01", "RAL-01"]);
  });

  it("formats cents as US dollars", () => {
    expect(formatCents(18900)).toBe("$189.00");
    expect(formatCents(45)).toBe("$0.45");
  });
});
