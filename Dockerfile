FROM webdevops/php-nginx:8.4

COPY . /app
COPY startup.sh /startup.sh
RUN chmod +x /startup.sh

COPY deny-dotfiles.conf /opt/docker/etc/nginx/vhost.common.d/10-deny-dotfiles.conf

ENTRYPOINT ["/startup.sh"]
# No CMD needed – the base image’s CMD ["supervisord"] will be used as arguments
EXPOSE 8