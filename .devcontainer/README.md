# Dev Container for CTA Highlights

This directory contains configuration for a VS Code development container that provides a complete, pre-configured development environment.

## What is a Dev Container?

A development container (dev container) is a Docker container configured specifically for development. It includes all the tools, runtimes, and dependencies needed for the project, eliminating the "works on my machine" problem.

## Features

This dev container includes:

- **PHP 8.2** with all WordPress-required extensions
- **Composer 2** for PHP dependency management
- **Node.js 18 LTS** with npm
- **Xdebug** configured for debugging
- **Docker CLI** for running wp-env inside the container
- **Git** and **GitHub CLI**
- **All VS Code extensions** for WordPress development pre-installed

## Requirements

- **VS Code** - [Download](https://code.visualstudio.com/)
- **Docker Desktop** - [Download](https://www.docker.com/products/docker-desktop)
- **Dev Containers extension** - [Install](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers)

## How to Use

### First Time Setup

1. **Install Prerequisites**
   - Install VS Code
   - Install Docker Desktop and start it
   - Install the "Dev Containers" extension in VS Code

2. **Open in Container**
   - Open the plugin folder in VS Code
   - Press `F1` (or `Ctrl/Cmd + Shift + P`)
   - Select "Dev Containers: Reopen in Container"
   - Wait for the container to build (first time takes ~5-10 minutes)

3. **Start Developing**
   ```bash
   # Dependencies are auto-installed, but if needed:
   npm install
   composer install

   # Start WordPress environment
   npm run env:start

   # Run tests
   npm run test:quick

   # Build plugin
   npm run build:zip
   ```

### Daily Workflow

1. **Open VS Code** in the plugin directory
2. VS Code will automatically detect the dev container
3. Click "Reopen in Container" when prompted (or press `F1` → "Reopen in Container")
4. Container starts in seconds (after first build)
5. Terminal opens with all tools ready

### Accessing WordPress

When you run `npm run env:start`, WordPress will be available at:
- **Development:** http://localhost:8888 (admin/password)
- **Testing:** http://localhost:8889 (admin/password)

Ports are automatically forwarded from the container to your host machine.

## What Gets Installed

### VS Code Extensions

The dev container automatically installs:

**PHP:**
- Intelephense (PHP IntelliSense)
- PHP Debug (Xdebug support)
- PHP Sniffer (PHPCS integration)

**JavaScript:**
- ESLint
- Prettier

**WordPress:**
- WordPress Toolbox

**Testing:**
- Test Explorer

**Utilities:**
- GitLens
- EditorConfig
- Code Spell Checker
- Docker

### Command-Line Tools

- PHP 8.2 (with WordPress extensions)
- Composer 2
- Node.js 18 LTS
- npm (latest)
- Docker CLI (for wp-env)
- Git
- GitHub CLI
- zip, unzip, rsync, mysql client

## Configuration Files

- `devcontainer.json` - Main configuration (extensions, settings, ports)
- `docker-compose.yml` - Docker Compose setup
- `Dockerfile` - Container image definition
- `README.md` - This file

## Customization

### Adding VS Code Extensions

Edit `devcontainer.json` and add extension IDs to the `extensions` array:

```json
"extensions": [
  "existing.extension",
  "new.extension-id"
]
```

### Adding System Packages

Edit `Dockerfile` and add packages to the `apt-get install` command:

```dockerfile
RUN apt-get update && apt-get install -y \
    your-package-name \
    && apt-get clean -y
```

### Adding PHP Extensions

Edit `Dockerfile` and add to the `install-php-extensions` command:

```dockerfile
RUN install-php-extensions \
    existing-extension \
    your-new-extension
```

## Debugging

### PHP Debugging with Xdebug

1. Xdebug is pre-configured in the container
2. Set breakpoints in VS Code (click left margin in PHP files)
3. Press `F5` or go to Run → Start Debugging
4. Xdebug will connect automatically when WordPress code runs

### Troubleshooting

**Container won't build:**
- Check Docker Desktop is running
- Try: `F1` → "Dev Containers: Rebuild Container"

**Can't access WordPress:**
- Ensure `npm run env:start` completed successfully
- Check ports 8888/8889 aren't used by other apps
- Check VS Code port forwarding: `F1` → "Ports: Focus on Ports View"

**Slow on Windows:**
- Ensure WSL 2 is installed: `wsl --install`
- Ensure Docker uses WSL 2 backend (Docker Desktop settings)
- Clone repo inside WSL filesystem for better performance

**Changes not reflected:**
- Rebuild container: `F1` → "Dev Containers: Rebuild Container"
- Clear wp-env: `npm run env:clean` then `npm run env:start`

## Benefits Over Local Setup

✅ **Zero configuration** - Everything pre-installed and configured
✅ **Team consistency** - Everyone has identical environment
✅ **Isolated** - Doesn't affect your system PHP/Composer
✅ **Version controlled** - Configuration is in Git
✅ **Easy updates** - Change Dockerfile, rebuild container
✅ **Cross-platform** - Works identically on Windows/Mac/Linux

## Optional vs Required

**This dev container is 100% optional.**

- ✅ Use it: Enhanced VS Code experience with zero setup
- ✅ Don't use it: Standard Docker-based workflow still works perfectly

See [CONTRIBUTING.md](../CONTRIBUTING.md) for the standard (non-dev-container) workflow.

## Learn More

- [VS Code Dev Containers Documentation](https://code.visualstudio.com/docs/devcontainers/containers)
- [Dev Container Specification](https://containers.dev/)
- [Dev Container Images](https://github.com/devcontainers/images)

---

**Questions?** Open a [GitHub Discussion](https://github.com/your-org/cta-highlights/discussions)
