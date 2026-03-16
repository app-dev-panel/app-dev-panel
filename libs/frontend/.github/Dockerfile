FROM nginx:alpine

LABEL org.opencontainers.image.source=https://github.com/app-dev-panel/app-dev-panel
LABEL org.opencontainers.image.description="App Dev Panel"
LABEL org.opencontainers.image.licenses=BSD-3-Clause

RUN rm -rf /usr/share/nginx/html/*
COPY js-build /usr/share/nginx/html

ENTRYPOINT ["nginx", "-g", "daemon off;"]
