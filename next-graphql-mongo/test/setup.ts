import { MongoMemoryServer } from "mongodb-memory-server";
import type { TestProject } from "vitest/node";

declare module "vitest" {
  export interface ProvidedContext {
    mongoUri: string;
  }
}

/** Uses MONGODB_URI when set (CI service container); otherwise starts an in-memory mongod. */
export default async function setup(project: TestProject) {
  if (process.env.MONGODB_URI) {
    project.provide("mongoUri", process.env.MONGODB_URI);
    return;
  }
  const server = await MongoMemoryServer.create();
  project.provide("mongoUri", server.getUri());
  return () => server.stop();
}
