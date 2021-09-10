FROM solr:latest

LABEL maintainer="Klaas Poortinga"

RUN cp -r /opt/solr/server/solr/configsets /var/solr/data

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["solr-foreground"]
