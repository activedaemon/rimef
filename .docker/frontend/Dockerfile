# syntax=docker/dockerfile:1.6
# Image de dev commune aux deux SPA : espace des membres (frontend/app)
# et supervision (frontend/supervisor). Le code est monté en volume.
FROM node:24-alpine

# wget : utilisé par le healthcheck
RUN apk add --no-cache wget

WORKDIR /app

COPY --chmod=755 .docker/frontend/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

# npm install est lancé au démarrage par l'entrypoint, avec l'utilisateur
# de l'hôte, pour que node_modules lui appartienne
EXPOSE 5173

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["npm", "run", "dev"]
