import { type Db, MongoClient } from "mongodb";
import { afterAll, beforeAll, inject } from "vitest";
import { ensureIndexes } from "../lib/store";

/** A throwaway database per test file, dropped afterwards. */
export function testDb(): () => Db {
  let client: MongoClient;
  let db: Db;
  beforeAll(async () => {
    client = new MongoClient(inject("mongoUri"));
    db = client.db(`test_${crypto.randomUUID().slice(0, 8)}`);
    await ensureIndexes(db);
  });
  afterAll(async () => {
    await db.dropDatabase();
    await client.close();
  });
  return () => db;
}
