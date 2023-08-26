# vim:set ft=dockerfile:
FROM postgres:latest
#FROM postgres:11-alpine
# Custom initialization scripts
COPY ./init-db.sh   /docker-entrypoint-initdb.d/10-init-db.sh
