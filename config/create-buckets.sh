#!/bin/sh

#Cria todos buckets.
minio server /data --console-address ":9001" &
MINIO_PID=$!

sleep 10

mc alias set local http://localhost:9000 $MINIO_ACCESS_KEY $MINIO_SECRET_KEY

# Cria 2 buckets e seta as policies.
mc mb local/localbucket
mc mb local/privatelocalbucket

mc anonymous set public local/localbucket

wait $MINIO_PID
