import { afterEach, describe, expect, inject, it, vi } from "vitest";

const load = async () => (await import("../lib/db")).getDb;

describe("getDb", () => {
  afterEach(() => {
    vi.unstubAllEnvs();
    vi.resetModules();
  });

  it("fails clearly without MONGODB_URI and retries once it is set", async () => {
    vi.stubEnv("MONGODB_URI", "");
    const getDb = await load();
    await expect(getDb()).rejects.toThrow("MONGODB_URI is not set");

    vi.stubEnv("MONGODB_URI", inject("mongoUri"));
    vi.stubEnv("MONGODB_DB", `test_${crypto.randomUUID().slice(0, 8)}`);
    const db = await getDb();
    expect(await db.collection("projects").countDocuments()).toBe(0);
    await db.dropDatabase();
  });

  it("seeds an empty database when SEED_DEMO_DATA=true and reuses the connection", async () => {
    vi.stubEnv("MONGODB_URI", inject("mongoUri"));
    vi.stubEnv("MONGODB_DB", `test_${crypto.randomUUID().slice(0, 8)}`);
    vi.stubEnv("SEED_DEMO_DATA", "true");
    const getDb = await load();
    const db = await getDb();
    expect(await getDb()).toBe(db);
    expect(await db.collection("projects").countDocuments()).toBe(3);
    await db.dropDatabase();
  });
});
