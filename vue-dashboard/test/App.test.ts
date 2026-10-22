import { flushPromises, mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import App from "../src/App.vue";
import { createMockApi } from "../src/api";
import type { InventoryApi } from "../src/types";

async function mountApp(api: InventoryApi = createMockApi()) {
  const wrapper = mount(App, { props: { api, pageSize: 4 } });
  await flushPromises();
  return wrapper;
}

type Wrapper = Awaited<ReturnType<typeof mountApp>>;
const skus = (wrapper: Wrapper) => wrapper.findAll("tbody tr td:first-child").map((td) => td.text());
const pageLabel = (wrapper: Wrapper) => wrapper.get("[data-test=page]").text();

async function next(wrapper: Wrapper) {
  await wrapper.findAll("nav button")[1]!.trigger("click");
  await flushPromises();
}

describe("App", () => {
  it("shows the headline counts and warehouses", async () => {
    const wrapper = await mountApp();
    expect(wrapper.findAll(".card")).toHaveLength(5);
    expect(wrapper.text()).toContain("Active products");
    expect(wrapper.text()).toContain("CLT-01 (Charlotte)");
  });

  it("renders the first page and moves forward and back", async () => {
    const wrapper = await mountApp();
    expect(skus(wrapper)).toHaveLength(4);
    expect(pageLabel(wrapper)).toBe("1–4 of 10");
    expect(wrapper.get("nav button").attributes("disabled")).toBeDefined();

    await next(wrapper);
    expect(pageLabel(wrapper)).toBe("5–8 of 10");
    await next(wrapper);
    expect(pageLabel(wrapper)).toBe("9–10 of 10");
    expect(wrapper.findAll("nav button")[1]!.attributes("disabled")).toBeDefined();

    await wrapper.get("nav button").trigger("click");
    await flushPromises();
    expect(pageLabel(wrapper)).toBe("5–8 of 10");
  });

  it("filters by search text and resets to the first page", async () => {
    const wrapper = await mountApp();
    await next(wrapper);

    await wrapper.get("input[type=search]").setValue("tape");
    await flushPromises();
    expect(skus(wrapper)).toEqual(["TAPE-48"]);
    expect(pageLabel(wrapper)).toBe("1–1 of 1");
  });

  it("filters low stock and highlights those rows", async () => {
    const wrapper = await mountApp();
    await wrapper.get("input[type=checkbox]").setValue(true);
    await flushPromises();
    const rows = wrapper.findAll("tbody tr");
    expect(rows.length).toBeGreaterThan(0);
    for (const row of rows) expect(row.classes()).toContain("low");
  });

  it("shows an empty state when nothing matches", async () => {
    const wrapper = await mountApp();
    await wrapper.get("input[type=search]").setValue("no-such-sku");
    await flushPromises();
    expect(skus(wrapper)).toEqual([]);
    expect(pageLabel(wrapper)).toBe("No products");
  });

  it("shows an error when the API fails", async () => {
    const failing: InventoryApi = {
      ...createMockApi(),
      getDashboard: () => Promise.reject(new Error("API unavailable")),
      listProducts: () => Promise.reject(new Error("API unavailable")),
    };
    const wrapper = await mountApp(failing);
    expect(wrapper.get("[role=alert]").text()).toBe("API unavailable");
  });
});
