import { getDb } from "../../../lib/db";
import { createGraphQL } from "../../../lib/graphql";

const { handleRequest } = createGraphQL(getDb);

export { handleRequest as GET, handleRequest as OPTIONS, handleRequest as POST };
