FROM node:21.7-alpine AS builder

RUN apk add --update --no-cache git nano curl python3 py3-setuptools make g++\
   && rm -rf /var/cache/apk/*

WORKDIR /app

# Root monorepo package
COPY lerna.json package*.json ./

# Dev Panel
COPY packages/yii-dev-panel/package*.json packages/yii-dev-panel/

# Dev Panel SDK
COPY packages/yii-dev-panel-sdk/package*.json packages/yii-dev-panel-sdk/

# Dev Toolbar
COPY packages/yii-dev-toolbar/package*.json packages/yii-dev-toolbar/

RUN npm ci

COPY . .

ARG VITE_BUILD_ID
ENV VITE_BUILD_ID=$VITE_BUILD_ID

RUN npm run build:dev

FROM nginx:alpine AS target

LABEL org.opencontainers.image.source=https://github.com/yiisoft/yii-dev-panel
LABEL org.opencontainers.image.description="Yii Dev Panel"
LABEL org.opencontainers.image.licenses=BSD-3-Clause

RUN rm -rf /usr/share/nginx/html/*
COPY --from=builder /app/packages/yii-dev-panel/dist /usr/share/nginx/html

ENTRYPOINT ["nginx", "-g", "daemon off;"]
