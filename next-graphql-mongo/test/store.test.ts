import { GraphQLError } from "graphql";
import { ObjectId } from "mongodb";
import { beforeAll, describe, expect, it } from "vitest";
import { addTask, getProject, listTasks, projectSummaries, seed, setTaskStatus } from "../lib/store";
import { testDb } from "./helpers";

const db = testDb();

const code = async (promise: Promise<unknown>) => {
  const error = await promise.then(() => undefined, (e: unknown) => e);
  expect(error).toBeInstanceOf(GraphQLError);
  return (error as GraphQLError).extensions.code;
};

describe("store", () => {
  beforeAll(async () => {
    expect(await seed(db())).toBe(true);
  });

  it("seeds only an empty database", async () => {
    expect(await seed(db())).toBe(false);
  });

  it("summarises projects sorted by name with task counts", async () => {
    const projects = await projectSummaries(db());
    expect(projects.map((p) => [p.name, p.taskCount, p.doneCount])).toEqual([
      ["Clinic booking portal", 3, 1],
      ["Fleet telemetry", 2, 1],
      ["Warehouse kiosk", 1, 0],
    ]);
  });

  it("gets one project, or null for an unknown id", async () => {
    const [first] = await projectSummaries(db());
    expect((await getProject(db(), first!._id.toHexString()))?.name).toBe(first!.name);
    expect(await getProject(db(), new ObjectId().toHexString())).toBeNull();
  });

  it("rejects malformed ids as bad input", async () => {
    expect(await code(getProject(db(), "not-an-id"))).toBe("BAD_USER_INPUT");
    expect(await code(setTaskStatus(db(), "nope", "DONE"))).toBe("BAD_USER_INPUT");
  });

  it("adds a trimmed TODO task and updates the counts", async () => {
    const [, fleet] = await projectSummaries(db());
    const task = await addTask(db(), fleet!._id.toHexString(), "  Driver app login  ");
    expect(task).toMatchObject({ title: "Driver app login", status: "TODO" });
    expect((await getProject(db(), fleet!._id.toHexString()))?.taskCount).toBe(3);
  });

  it("validates the title and the project", async () => {
    const [p] = await projectSummaries(db());
    expect(await code(addTask(db(), p!._id.toHexString(), "   "))).toBe("BAD_USER_INPUT");
    expect(await code(addTask(db(), p!._id.toHexString(), "x".repeat(201)))).toBe("BAD_USER_INPUT");
    expect(await code(addTask(db(), new ObjectId().toHexString(), "Orphan"))).toBe("NOT_FOUND");
  });

  it("lists tasks oldest first, optionally by status", async () => {
    const [clinic] = await projectSummaries(db());
    const all = await listTasks(db(), clinic!._id);
    expect(all.map((t) => t.title)).toEqual(["Design appointment schema", "Build availability API", "Email reminders"]);
    expect((await listTasks(db(), clinic!._id, "TODO")).map((t) => t.title)).toEqual(["Email reminders"]);
  });

  it("changes a task status", async () => {
    const [, , kiosk] = await projectSummaries(db());
    const [task] = await listTasks(db(), kiosk!._id);
    expect((await setTaskStatus(db(), task!._id.toHexString(), "DONE")).status).toBe("DONE");
    expect((await getProject(db(), kiosk!._id.toHexString()))?.doneCount).toBe(1);
    expect(await code(setTaskStatus(db(), new ObjectId().toHexString(), "DONE"))).toBe("NOT_FOUND");
    // @ts-expect-error: the GraphQL enum blocks this at the API edge; the store checks too
    expect(await code(setTaskStatus(db(), task!._id.toHexString(), "ARCHIVED"))).toBe("BAD_USER_INPUT");
  });
});
