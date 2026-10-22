import { GraphQLError } from "graphql";
import { type Db, ObjectId } from "mongodb";

export const TASK_STATUSES = ["TODO", "IN_PROGRESS", "DONE"] as const;
export type TaskStatus = (typeof TASK_STATUSES)[number];

export interface ProjectDoc {
  _id: ObjectId;
  name: string;
  client: string;
  createdAt: Date;
}

export interface TaskDoc {
  _id: ObjectId;
  projectId: ObjectId;
  title: string;
  status: TaskStatus;
  createdAt: Date;
}

export interface ProjectSummary extends ProjectDoc {
  taskCount: number;
  doneCount: number;
}

const projects = (db: Db) => db.collection<ProjectDoc>("projects");
const tasks = (db: Db) => db.collection<TaskDoc>("tasks");

const badInput = (message: string) => new GraphQLError(message, { extensions: { code: "BAD_USER_INPUT" } });
const notFound = (what: string) => new GraphQLError(`${what} not found`, { extensions: { code: "NOT_FOUND" } });

/** Parses an id from the client; malformed ids are a client error, not a server crash. */
function objectId(id: string, what: string): ObjectId {
  if (!ObjectId.isValid(id)) throw badInput(`Invalid ${what} id`);
  return new ObjectId(id);
}

export async function ensureIndexes(db: Db): Promise<void> {
  await tasks(db).createIndex({ projectId: 1, createdAt: 1 });
  await projects(db).createIndex({ name: 1 }, { unique: true });
}

/** Projects with task counts in one aggregation, so listing projects is not N+1. */
export async function projectSummaries(db: Db, filter: { _id?: ObjectId } = {}): Promise<ProjectSummary[]> {
  return projects(db)
    .aggregate<ProjectSummary>([
      { $match: filter },
      { $lookup: { from: "tasks", localField: "_id", foreignField: "projectId", as: "tasks", pipeline: [{ $project: { status: 1 } }] } },
      {
        $addFields: {
          taskCount: { $size: "$tasks" },
          doneCount: { $size: { $filter: { input: "$tasks", cond: { $eq: ["$$this.status", "DONE"] } } } },
        },
      },
      { $project: { tasks: 0 } },
      { $sort: { name: 1 } },
    ])
    .toArray();
}

export async function getProject(db: Db, id: string): Promise<ProjectSummary | null> {
  const [project] = await projectSummaries(db, { _id: objectId(id, "project") });
  return project ?? null;
}

export async function listTasks(db: Db, projectId: ObjectId, status?: TaskStatus): Promise<TaskDoc[]> {
  return tasks(db)
    .find({ projectId, ...(status && { status }) })
    .sort({ createdAt: 1, _id: 1 })
    .toArray();
}

export async function addTask(db: Db, projectId: string, rawTitle: string): Promise<TaskDoc> {
  const title = rawTitle.trim();
  if (title.length < 1 || title.length > 200) throw badInput("Title must be 1-200 characters");
  const pid = objectId(projectId, "project");
  if (!(await projects(db).countDocuments({ _id: pid }, { limit: 1 }))) throw notFound("Project");

  const task: TaskDoc = { _id: new ObjectId(), projectId: pid, title, status: "TODO", createdAt: new Date() };
  await tasks(db).insertOne(task);
  return task;
}

export async function setTaskStatus(db: Db, id: string, status: TaskStatus): Promise<TaskDoc> {
  if (!TASK_STATUSES.includes(status)) throw badInput("Unknown status");
  const task = await tasks(db).findOneAndUpdate({ _id: objectId(id, "task") }, { $set: { status } }, { returnDocument: "after" });
  if (!task) throw notFound("Task");
  return task;
}

/** Fictional demo data, inserted only into an empty database. */
export async function seed(db: Db): Promise<boolean> {
  if (await projects(db).estimatedDocumentCount()) return false;
  const now = Date.now();
  const at = (minutes: number) => new Date(now - minutes * 60_000);
  const demo: Array<[string, string, Array<[string, TaskStatus]>]> = [
    ["Clinic booking portal", "Maple Street Clinic (fictional)", [["Design appointment schema", "DONE"], ["Build availability API", "IN_PROGRESS"], ["Email reminders", "TODO"]]],
    ["Fleet telemetry", "Northwind Hauling (fictional)", [["Ingest GPS pings", "DONE"], ["Geofence alerts", "TODO"]]],
    ["Warehouse kiosk", "Contoso Supply (fictional)", [["Barcode scan screen", "TODO"]]],
  ];
  for (const [i, [name, client, items]] of demo.entries()) {
    const projectId = new ObjectId();
    await projects(db).insertOne({ _id: projectId, name, client, createdAt: at(1000 - i) });
    await tasks(db).insertMany(
      items.map(([title, status], j) => ({ _id: new ObjectId(), projectId, title, status, createdAt: at(900 - i * 10 - j) })),
    );
  }
  return true;
}
