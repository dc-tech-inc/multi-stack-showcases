import type { Dashboard, Product, Warehouse } from "../types";

// Fictional data. Ids are fixed UUIDs so tests and screenshots are stable.

export const warehouses: Warehouse[] = [
  { id: "7d0f6c9e-1a2b-4c3d-8e4f-000000000001", code: "CLT-01", name: "Northside Depot", city: "Charlotte" },
  { id: "7d0f6c9e-1a2b-4c3d-8e4f-000000000002", code: "RAL-01", name: "Triangle Hub", city: "Raleigh" },
];

const p = (n: number, sku: string, name: string, unit: string, reorderPoint: number, unitCostCents: number, totalQuantity: number, active = true): Product => ({
  id: `3f1e2d4c-5b6a-4789-9abc-${String(n).padStart(12, "0")}`,
  sku,
  name,
  unit,
  reorderPoint,
  unitCostCents,
  active,
  totalQuantity,
  lowStock: active && totalQuantity <= reorderPoint,
});

export const products: Product[] = [
  p(1, "BOX-SM", "Shipping box, small", "each", 200, 45, 820),
  p(2, "BOX-LG", "Shipping box, large", "each", 150, 95, 120),
  p(3, "TAPE-48", "Packing tape 48 mm", "roll", 60, 310, 58),
  p(4, "LBL-4X6", "Thermal labels 4x6", "roll", 40, 1250, 210),
  p(5, "PLT-STD", "Wooden pallet", "each", 30, 1800, 12),
  p(6, "GLV-M", "Work gloves, medium", "pair", 50, 420, 75),
  p(7, "WRAP-18", "Stretch wrap 18 in", "roll", 25, 2200, 25),
  p(8, "SCN-01", "Handheld barcode scanner", "each", 4, 18900, 9),
  p(9, "CRT-BLU", "Plastic crate, blue", "each", 80, 650, 300),
  p(10, "OLD-BIN", "Retired storage bin", "each", 10, 500, 0, false),
];

export const dashboard: Dashboard = {
  products: products.filter((x) => x.active).length,
  lowStock: products.filter((x) => x.lowStock).length,
  openPurchaseOrders: 3,
  transfersInTransit: 1,
  movementsLast7Days: 42,
};
