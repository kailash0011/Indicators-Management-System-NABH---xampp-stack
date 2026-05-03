#!/bin/bash
set -e

MYSQL_DATADIR=/home/runner/mysql_data
MYSQL_RUN=/home/runner/mysql_run
MYSQL_LOGS=/home/runner/mysql_logs
MYSQL_TMP=/home/runner/mysql_tmp
MYCNF=/home/runner/workspace/my.cnf
MYSQL_BIN=/nix/store/s2lbn1axpc79kwnc829k5idkwabfq459-mysql-8.0.42/bin

mkdir -p "$MYSQL_DATADIR" "$MYSQL_RUN" "$MYSQL_LOGS" "$MYSQL_TMP"

# Initialize MySQL data directory if not done yet
if [ ! -d "$MYSQL_DATADIR/mysql" ]; then
    echo "Initializing MySQL data directory..."
    "$MYSQL_BIN/mysqld" --defaults-file="$MYCNF" --initialize-insecure --user=runner 2>&1
    echo "MySQL initialized."
fi

# Start MySQL if not already running
if [ ! -f "$MYSQL_RUN/mysql.pid" ] || ! kill -0 "$(cat $MYSQL_RUN/mysql.pid)" 2>/dev/null; then
    echo "Starting MySQL..."
    "$MYSQL_BIN/mysqld" --defaults-file="$MYCNF" --user=runner &
    MYSQLD_PID=$!

    # Wait for MySQL to be ready
    echo "Waiting for MySQL to start..."
    for i in $(seq 1 30); do
        if "$MYSQL_BIN/mysql" --defaults-file="$MYCNF" -u root -e "SELECT 1;" > /dev/null 2>&1; then
            echo "MySQL is ready."
            break
        fi
        sleep 1
    done
fi

# Run database setup (schema + seed) if nabh_indicators DB doesn't exist
DB_EXISTS=$("$MYSQL_BIN/mysql" --defaults-file="$MYCNF" -u root -sse "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='nabh_indicators';" 2>/dev/null || echo "")
if [ -z "$DB_EXISTS" ]; then
    echo "Setting up nabh_indicators database..."
    "$MYSQL_BIN/mysql" --defaults-file="$MYCNF" -u root < /home/runner/workspace/sql/schema.sql
    # Remove USE statement from seed (schema already switches DB)
    grep -v "^USE" /home/runner/workspace/sql/seed.sql | "$MYSQL_BIN/mysql" --defaults-file="$MYCNF" -u root nabh_indicators
    echo "Database seeded."

    # Create users via PHP CLI
    php /home/runner/workspace/run_setup.php
    echo "Users created."
fi

echo "Starting PHP built-in server on 0.0.0.0:5000..."
cd /home/runner/workspace
exec php -S 0.0.0.0:5000 router.php
