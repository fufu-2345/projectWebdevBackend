FROM node:16

RUN npm install -g pnpm

WORKDIR /app
COPY . .

RUN pnpm install

CMD ["pnpm", "dev"]
