import { type Db, MongoClient } from "mongodb";
import { ensureIndexes, seed } from "./store";

let client: MongoClient | undefined;
let ready: Promise<Db> | undefined;

/**
 * One MongoClient per server process (the driver pools connections and
 * connects on first use). Indexes are created once; with SEED_DEMO_DATA=true
 * an empty database gets the fictional demo data.
 */
export function getDb(): Promise<Db> {
  ready ??= (async () => {
    const uri = process.env.MONGODB_URI;
    if (!uri) throw new Error("MONGODB_URI is not set (see .env.example)");
    client = new MongoClient(uri);
    const db = client.db(process.env.MONGODB_DB ?? "showcase");
    await ensureIndexes(db);
    if (process.env.SEED_DEMO_DATA === "true") await seed(db);
    return db;
  })().catch((error: unknown) => {
    ready = undefined; // retry on the next request instead of caching the failure
    throw error;
  });
  return ready;
}
