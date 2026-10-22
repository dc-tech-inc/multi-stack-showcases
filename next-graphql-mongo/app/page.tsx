import { getDb } from "../lib/db";
import { projectSummaries } from "../lib/store";

// Reads MongoDB on every request (Server Component, no client JavaScript).
export const dynamic = "force-dynamic";

export default async function Home() {
  const projects = await projectSummaries(await getDb());

  return (
    <main>
      <h1>Projects</h1>
      <p className="muted">
        Server-rendered from MongoDB. The same data is served by GraphQL at <a href="/api/graphql">/api/graphql</a>. Fictional data.
      </p>
      {projects.length === 0 ? (
        <p>No projects yet. Start with SEED_DEMO_DATA=true to load the demo data.</p>
      ) : (
        <ul className="projects">
          {projects.map((p) => {
            const pct = p.taskCount ? Math.round((p.doneCount / p.taskCount) * 100) : 0;
            return (
              <li key={p._id.toHexString()}>
                <strong>{p.name}</strong>
                <span className="muted">{p.client}</span>
                <progress max={100} value={pct} aria-label={`${p.name} progress`} />
                <span>
                  {p.doneCount}/{p.taskCount} tasks done
                </span>
              </li>
            );
          })}
        </ul>
      )}
      <h2>Try a query</h2>
      <pre>{`query {
  projects { name doneCount taskCount tasks(status: TODO) { title } }
}`}</pre>
    </main>
  );
}
