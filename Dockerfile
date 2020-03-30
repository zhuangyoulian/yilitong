FROM ubuntu:14.04
LABEL maintainer="cdtaogang <cdtaogang@163.com>"
RUN apt-get update && apt-get install -y redis-server
EXPOSE 6380
ENTRYPOINT ["/usr/bin/redis-server"]
