import { defineConfig } from "vitest/config";

export default defineConfig({
  // Node loads graphql-yoga (external) with graphql's CommonJS build; resolve our
  // imports to the same file so there is one GraphQLError class (no dual-package hazard).
  resolve: { alias: [{ find: /^graphql$/, replacement: "graphql/index.js" }] },
  test: {
    environment: "node",
    globalSetup: ["test/setup.ts"],
    hookTimeout: 120_000, // first local run downloads the mongod binary
    coverage: { include: ["lib/**"], reporter: ["text", "text-summary"] },
  },
});
