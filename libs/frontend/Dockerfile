FROM node:21.7-alpine AS builder

RUN apk add --update --no-cache git nano curl python3 py3-setuptools make g++\
   && rm -rf /var/cache/apk/*

WORKDIR /app

# Root monorepo package
COPY package*.json ./

# Panel
COPY packages/panel/package*.json packages/panel/

# SDK
COPY packages/sdk/package*.json packages/sdk/

# Toolbar
COPY packages/toolbar/package*.json packages/toolbar/

RUN npm ci

COPY . .

ARG VITE_BUILD_ID
ENV VITE_BUILD_ID=$VITE_BUILD_ID

RUN npm run build:dev

FROM nginx:alpine AS target

LABEL org.opencontainers.image.source=https://github.com/app-dev-panel/app-dev-panel
LABEL org.opencontainers.image.description="App Dev Panel"
LABEL org.opencontainers.image.licenses=BSD-3-Clause

RUN rm -rf /usr/share/nginx/html/*
COPY --from=builder /app/packages/panel/dist /usr/share/nginx/html

ENTRYPOINT ["nginx", "-g", "daemon off;"]
