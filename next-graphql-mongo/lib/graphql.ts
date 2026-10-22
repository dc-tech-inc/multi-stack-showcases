import { createSchema, createYoga } from "graphql-yoga";
import type { Db } from "mongodb";
import { addTask, getProject, listTasks, projectSummaries, setTaskStatus, type ProjectSummary, type TaskDoc, type TaskStatus } from "./store";

export const typeDefs = /* GraphQL */ `
  enum TaskStatus {
    TODO
    IN_PROGRESS
    DONE
  }

  type Task {
    id: ID!
    title: String!
    status: TaskStatus!
    "ISO 8601 timestamp"
    createdAt: String!
  }

  type Project {
    id: ID!
    name: String!
    client: String!
    taskCount: Int!
    doneCount: Int!
    tasks(status: TaskStatus): [Task!]!
  }

  type Query {
    projects: [Project!]!
    project(id: ID!): Project
  }

  type Mutation {
    addTask(projectId: ID!, title: String!): Task!
    setTaskStatus(id: ID!, status: TaskStatus!): Task!
  }
`;

interface Context {
  db: Db;
}

const schema = createSchema<Context>({
  typeDefs,
  resolvers: {
    Query: {
      projects: (_: unknown, __: unknown, { db }: Context) => projectSummaries(db),
      project: (_: unknown, { id }: { id: string }, { db }: Context) => getProject(db, id),
    },
    Mutation: {
      addTask: (_: unknown, a: { projectId: string; title: string }, { db }: Context) => addTask(db, a.projectId, a.title),
      setTaskStatus: (_: unknown, a: { id: string; status: TaskStatus }, { db }: Context) => setTaskStatus(db, a.id, a.status),
    },
    Project: {
      id: (p: ProjectSummary) => p._id.toHexString(),
      tasks: (p: ProjectSummary, { status }: { status?: TaskStatus }, { db }: Context) => listTasks(db, p._id, status),
    },
    Task: {
      id: (t: TaskDoc) => t._id.toHexString(),
      createdAt: (t: TaskDoc) => t.createdAt.toISOString(),
    },
  },
});

/**
 * GraphQL Yoga over the Fetch API, so the same instance serves the Next.js
 * route handler and the tests (yoga.fetch). Unexpected errors are masked;
 * GraphQLErrors thrown by the store (BAD_USER_INPUT, NOT_FOUND) reach the client.
 */
export function createGraphQL(getDb: () => Promise<Db>) {
  return createYoga<object, Context>({
    schema,
    graphqlEndpoint: "/api/graphql",
    fetchAPI: { Response },
    graphiql: process.env.NODE_ENV !== "production",
    context: async () => ({ db: await getDb() }),
  });
}
