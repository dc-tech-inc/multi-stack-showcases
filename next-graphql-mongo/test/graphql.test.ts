import { ObjectId } from "mongodb";
import { beforeAll, describe, expect, it } from "vitest";
import { createGraphQL } from "../lib/graphql";
import { seed } from "../lib/store";
import { testDb } from "./helpers";

const db = testDb();
const yoga = createGraphQL(async () => db());

interface Result {
  data?: Record<string, any>; // eslint-disable-line @typescript-eslint/no-explicit-any
  errors?: Array<{ message: string; extensions?: { code?: string } }>;
}

async function gql(query: string, variables?: Record<string, unknown>): Promise<Result> {
  const res = await yoga.fetch("http://localhost/api/graphql", {
    method: "POST",
    headers: { "content-type": "application/json" },
    body: JSON.stringify({ query, variables }),
  });
  return (await res.json()) as Result;
}

describe("GraphQL API", () => {
  beforeAll(async () => {
    await seed(db());
  });

  it("lists projects with counts and filtered tasks", async () => {
    const { data, errors } = await gql(`{ projects { name taskCount doneCount tasks(status: DONE) { title status } } }`);
    expect(errors).toBeUndefined();
    expect(data!.projects[0]).toEqual({
      name: "Clinic booking portal",
      taskCount: 3,
      doneCount: 1,
      tasks: [{ title: "Design appointment schema", status: "DONE" }],
    });
  });

  it("fetches a project by id and returns null for an unknown one", async () => {
    const { data } = await gql(`{ projects { id name } }`);
    const { id, name } = data!.projects[1];
    expect((await gql(`query ($id: ID!) { project(id: $id) { name } }`, { id })).data!.project).toEqual({ name });
    expect((await gql(`query ($id: ID!) { project(id: $id) { name } }`, { id: new ObjectId().toHexString() })).data!.project).toBeNull();
  });

  it("adds a task and moves it through the statuses", async () => {
    const { data } = await gql(`{ projects { id } }`);
    const projectId = data!.projects[2].id;

    const added = await gql(`mutation ($p: ID!, $t: String!) { addTask(projectId: $p, title: $t) { id title status createdAt } }`, { p: projectId, t: "Receipt printer" });
    expect(added.data!.addTask).toMatchObject({ title: "Receipt printer", status: "TODO" });
    expect(new Date(added.data!.addTask.createdAt).toISOString()).toBe(added.data!.addTask.createdAt);

    const moved = await gql(`mutation ($id: ID!) { setTaskStatus(id: $id, status: IN_PROGRESS) { status } }`, { id: added.data!.addTask.id });
    expect(moved.data!.setTaskStatus.status).toBe("IN_PROGRESS");
  });

  it("returns typed errors for bad input and missing records", async () => {
    const bad = await gql(`mutation { addTask(projectId: "x", title: "T") { id } }`);
    expect(bad.errors![0]!.extensions!.code).toBe("BAD_USER_INPUT");

    const missing = await gql(`mutation ($p: ID!) { addTask(projectId: $p, title: "T") { id } }`, { p: new ObjectId().toHexString() });
    expect(missing.errors![0]).toMatchObject({ message: "Project not found", extensions: { code: "NOT_FOUND" } });
  });

  it("rejects values outside the schema before any resolver runs", async () => {
    const { errors } = await gql(`mutation { setTaskStatus(id: "x", status: ARCHIVED) { id } }`);
    expect(errors![0]!.message).toMatch(/ARCHIVED/);
  });

  it("masks unexpected errors", async () => {
    const broken = createGraphQL(() => Promise.reject(new Error("connection string with secrets")));
    const res = await broken.fetch("http://localhost/api/graphql", {
      method: "POST",
      headers: { "content-type": "application/json" },
      body: JSON.stringify({ query: "{ projects { name } }" }),
    });
    const body = JSON.stringify(await res.json());
    expect(body).not.toContain("secrets");
    expect(body).toContain("Unexpected error");
  });
});
