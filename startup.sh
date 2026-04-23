#!/bin/sh

# Write all environment variables to .env in the format key=value
# This will overwrite any existing .env file
env > /app/.env

# If no arguments were passed, use supervisord as the default command
if [ $# -eq 0 ]; then
    set -- supervisord
fi

# Pass all arguments to the original entrypoint
exec /opt/docker/bin/entrypoint.sh "$@"