# Multi-stage build of the Next.js standalone server (output: "standalone").
FROM node:24-alpine AS deps
WORKDIR /app
COPY package.json package-lock.json ./
# The in-memory MongoDB used by local tests is not needed in the image.
RUN MONGOMS_DISABLE_POSTINSTALL=1 npm ci

FROM node:24-alpine AS build
WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY . .
ENV NEXT_TELEMETRY_DISABLED=1
RUN npm run build

FROM node:24-alpine AS run
WORKDIR /app
ENV NODE_ENV=production NEXT_TELEMETRY_DISABLED=1 PORT=3000 HOSTNAME=0.0.0.0
COPY --from=build --chown=node:node /app/.next/standalone ./
COPY --from=build --chown=node:node /app/.next/static ./.next/static
USER node
EXPOSE 3000
CMD ["node", "server.js"]
