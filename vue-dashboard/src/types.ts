// Shapes copied from the OpenAPI 3.1 document of warehouse-inventory-platform
// (components.schemas: Dashboard, Warehouse, Product, ProductListResponse).

export interface Dashboard {
  products: number;
  lowStock: number;
  openPurchaseOrders: number;
  transfersInTransit: number;
  movementsLast7Days: number;
}

export interface Warehouse {
  id: string;
  code: string;
  name: string;
  city: string;
}

export interface Product {
  id: string;
  sku: string;
  name: string;
  unit: string;
  reorderPoint: number;
  unitCostCents: number;
  active: boolean;
  /** Sum over all warehouses. */
  totalQuantity: number;
  lowStock: boolean;
}

/** GET /api/products query: limit 1-100 (default 25), offset >= 0. */
export interface ProductListQuery {
  q?: string;
  lowStock?: boolean;
  limit?: number;
  offset?: number;
}

export interface Page<T> {
  items: T[];
  total: number;
}

/** What the dashboard needs from the backend: GET /api/dashboard, /api/warehouses, /api/products. */
export interface InventoryApi {
  getDashboard(): Promise<Dashboard>;
  listWarehouses(): Promise<{ items: Warehouse[] }>;
  listProducts(query?: ProductListQuery): Promise<Page<Product>>;
}
