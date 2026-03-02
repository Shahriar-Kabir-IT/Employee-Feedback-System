# AGENTS.md

## Cursor Cloud specific instructions

### Overview

This is a vanilla PHP + MySQL web application — an **Employee Welfare Feedback & Case Management System** ("Ananta"). There are no package managers, build tools, or dependency files. The codebase consists of `.php` and `.html` files served directly by a web server.

### Services

| Service | How to start | Port |
|---------|-------------|------|
| MySQL | `sudo mkdir -p /var/run/mysqld && sudo chown mysql:mysql /var/run/mysqld && sudo mysqld --user=mysql --datadir=/var/lib/mysql &` then `sudo chmod 755 /var/run/mysqld/ && sudo chmod 777 /var/run/mysqld/mysqld.sock` | 3306 |
| PHP dev server | `php -S 0.0.0.0:8080` (run from `/workspace`) | 8080 |

### Gotchas

- **MySQL socket permissions**: After starting `mysqld`, the socket directory `/var/run/mysqld/` has restrictive permissions. You must `chmod 755` the directory and `chmod 777` the socket file, otherwise PHP PDO connections from the web server will fail with `SQLSTATE[HY000] [2002]`.
- **MySQL root password**: `db_config.php` expects `root` with an **empty password** and `mysql_native_password` auth plugin. Set this with: `ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';`
- **Database**: The application uses a database called `feedback_db`. The schema must be created manually (no migration files exist). See the setup session for the full `CREATE TABLE` statements.
- **Admin seeding**: `create_admin.php` only seeds one admin (pwpl). Additional admins for locations `abm`, `agl`, `ajl`, and a `superadmin` must be inserted manually via SQL or PHP.

### Lint

Run PHP syntax checks on all files:
```bash
for f in /workspace/*.php; do php -l "$f"; done
```

### Key URLs (when dev server is running on port 8080)

- Employee feedback forms: `http://localhost:8080/index_abm.html` (also `index_agl.html`, `index_ajl.html`, `index_pwpl.html`)
- Admin login: `http://localhost:8080/welfare_login.php`
- Test credentials: `abm`/`1234`, `agl`/`1234`, `ajl`/`1234`, `pwpl`/`1234`, `superadmin`/`1234`
