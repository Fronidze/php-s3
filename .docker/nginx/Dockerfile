FROM nginx:alpine
WORKDIR /app

COPY .docker/nginx/cors.conf /etc/nginx/cors.conf
COPY .docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY app/public /app/public