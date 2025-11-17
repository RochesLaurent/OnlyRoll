# ===========================================
# STAGE 1: Dependencies
# ===========================================
FROM node:20-alpine AS node_dependencies

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --prefer-offline --no-audit

# ===========================================
# STAGE 2: Builder
# ===========================================
FROM node_dependencies AS node_builder

COPY . .

ARG VITE_API_URL=http://localhost/api
ARG VITE_WS_URL=ws://localhost:3000

ENV VITE_API_URL=${VITE_API_URL} \
    VITE_WS_URL=${VITE_WS_URL} \
    NODE_ENV=production

RUN npm run build

# ===========================================
# STAGE 3: Development
# ===========================================
FROM node:20-alpine AS node_dev

WORKDIR /app

COPY --from=node_dependencies /app/node_modules ./node_modules
COPY package.json package-lock.json ./
COPY . .

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]

# ===========================================
# STAGE 4: Production (Nginx)
# ===========================================
FROM nginx:1.25-alpine AS nginx_prod

COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=node_builder /app/dist /usr/share/nginx/html

RUN chown -R nginx:nginx /usr/share/nginx/html \
    && chmod -R 755 /usr/share/nginx/html

HEALTHCHECK --interval=30s --timeout=3s CMD wget --quiet --tries=1 --spider http://localhost/health || exit 1

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
