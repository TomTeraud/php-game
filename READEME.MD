# PHP Game Project (Working Title)

This project is a PHP-based application featuring a WebSocket server for real-time communication, intended for game development. It uses Docker for a consistent development and production environment.

## Prerequisites

- Docker Engine
- Docker Compose

## Development Environment Setup

These steps will guide you through setting up and running the project for local development.

### 1. Clone the Repository (If applicable)

```bash
# git clone <your-repository-url>
# cd <your-project-directory>
```

### 2. Create Environment File

Copy the example environment file (if one exists, e.g., `.env.example`) or create a new one named `.env.local`. Populate `.env.local` with your local configuration. Crucially, ensure `MYSQL_ROOT_PASSWORD` is set to a strong password. Other variables like `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_DATABASE`, `MYSQL_PORT`, `REDIS_PORT` will also be needed.

**Example `.env.local`:**
```env
MYSQL_DATABASE=your_db_name
MYSQL_USER=your_db_user
MYSQL_PASSWORD=your_db_password
MYSQL_ROOT_PASSWORD=your_strong_root_password_here # Important!
MYSQL_PORT=3306
REDIS_PORT=6379
BUILD_TARGET=app_dev # Or app_prod
```

### 3. Build and Run Development Containers

This command uses the development-specific Compose file and your local environment variables to build images (if they don't exist or `--build` is specified) and start all services in detached mode (`-d`).

```bash
docker compose -f docker-compose.dev.yaml --env-file .env.local up --build -d
```

### 4. Install PHP Dependencies (Crucial for IDE Integration)

After the containers are running, execute `composer install` within the app container. This will install all PHP dependencies into the `./app/vendor` directory on your local machine (thanks to the bind mount configured in `docker-compose.dev.yaml`). This step is essential for your IDE (e.g., VS Code with Intelephense) to correctly find classes and provide autocompletion.

```bash
docker compose -f docker-compose.dev.yaml --env-file .env.local exec app composer install
```

If you see errors from your IDE about undefined classes (like Ratchet's), ensure this step was successful and your local `./app/vendor` directory is populated. Then, re-index your project in the IDE.

### 5. Accessing Services

- **Web Application (Nginx & PHP App):** http://localhost (or http://localhost:80)
- **phpMyAdmin:** http://localhost:8082
- **WebSocket Test Client** (if `test.html` is in `app/public`): http://localhost/test.html
- **WebSocket server endpoint** (for client JavaScript): `ws://localhost/ws/`

### 6. Development Workflow Notes

- **PHP Code Changes (WebSocket Server):**  
  If you modify PHP files for the WebSocket server (e.g., `app/src/WebSocket/Chat.php`), you need to restart the websocket service for the changes to take effect, as the PHP script runs as a long-lived process:
  ```bash
  docker compose -f docker-compose.dev.yaml --env-file .env.local restart websocket
  ```

- **PHP Code Changes (Web Application):**  
  Changes to PHP files handled by PHP-FPM (your regular web requests) should generally be reflected without a restart, though browser caching or opcache might sometimes require a hard refresh.

#### Xdebug Setup

To enable Xdebug for debugging your PHP application, use the following command. This merges the base development configuration with Xdebug-specific overrides.

```bash
docker compose -f docker-compose.dev.yaml -f docker-compose.xdebug.yaml --env-file .env.local up -d --build
```

Ensure your IDE is configured to listen for Xdebug connections (usually on port 9003). Path mappings might be required in your IDE to link paths in the container (e.g., `/var/www/html`) to paths on your local machine (e.g., `/home/tomst/php-game/app`).

#### Running Tests

To execute the PHPUnit test suite, run the following command from your project root on your host machine (assuming `./vendor/bin/phpunit` is accessible and configured for your project structure):

```bash
# If vendor/bin/phpunit is directly executable from host after composer install:
./app/vendor/bin/phpunit

# Or, execute tests inside the 'app' container:
docker compose -f docker-compose.dev.yaml --env-file .env.local exec app ./vendor/bin/phpunit
```

#### Shell Access

- **PHP Container (app service):**  
  To open an interactive shell (`sh`) inside the running app container (your main PHP environment):
  ```bash
  docker compose -f docker-compose.dev.yaml --env-file .env.local exec app sh
  ```

- **WebSocket Container (websocket service):**  
  To open an interactive shell (`sh`) inside the running websocket container:
  ```bash
  docker compose -f docker-compose.dev.yaml --env-file .env.local exec websocket sh
  ```

This can be useful for checking logs directly or running commands within the WebSocket server's environment.

---

## Production Build and Deployment

### 1. Build Production Docker Image

This command builds a Docker image specifically tagged for production using the `app_prod` target in your `./php/Dockerfile`.

```bash
docker build --target app_prod -t tomsterauds/php-composer:1.0 -f ./php/Dockerfile .
```
(Replace `tomsterauds/php-composer:1.0` with your desired image name and tag).

### 2. Push Image to Docker Hub (or other registry)

```bash
docker push tomsterauds/php-composer:1.0
```
*(You'll need to be logged into Docker Hub: `docker login`)*