# GoExport Server

A Laravel-based server for GoExport video rendering with headless display support.

[![Docker Hub](https://img.shields.io/docker/v/lexiandev/goexport-server?label=Docker%20Hub&logo=docker)](https://hub.docker.com/r/lexiandev/goexport-server)
[![Docker Pulls](https://img.shields.io/docker/pulls/lexiandev/goexport-server?logo=docker)](https://hub.docker.com/r/lexiandev/goexport-server)
[![Build and Publish](https://github.com/goexport/goexport-server/actions/workflows/docker-publish.yml/badge.svg)](https://github.com/goexport/goexport-server/actions/workflows/docker-publish.yml)

## Quick Start

### Production

```bash
docker compose up --build -d
```

Access the application at: http://localhost:8080

### Development (with VNC debugging)

```bash
docker compose -f docker-compose.dev.yml up --build
```

## VNC Access (Development Mode)

When running with `docker-compose.dev.yml`, you can view the virtual displays:

### Display :99 (GoExport Headless Display)

This is where GoExport renders content. Use this to debug rendering issues.

| Access Method       | URL/Port                       |
| ------------------- | ------------------------------ |
| VNC Client          | `localhost:5999`               |
| Web Browser (noVNC) | http://localhost:6099/vnc.html |

### Display :1 (Full XFCE4 Desktop)

A full desktop environment for debugging, running browsers manually, etc.

| Access Method       | URL/Port                       |
| ------------------- | ------------------------------ |
| VNC Client          | `localhost:5901`               |
| Web Browser (noVNC) | http://localhost:6080/vnc.html |

## Environment Variables

### VNC/Debug Features

| Variable               | Default | Description                                  |
| ---------------------- | ------- | -------------------------------------------- |
| `ENABLE_VNC_DISPLAY99` | `false` | Enable VNC viewer for :99 (GoExport display) |
| `ENABLE_VNC_DESKTOP`   | `false` | Enable full desktop on :1                    |
| `ENABLE_NOVNC`         | `false` | Enable web-based VNC clients                 |

### Display Settings

| Variable         | Default | Description                   |
| ---------------- | ------- | ----------------------------- |
| `DISPLAY`        | `:99`   | X display number for GoExport |
| `DISPLAY_WIDTH`  | `1920`  | Display width in pixels       |
| `DISPLAY_HEIGHT` | `1080`  | Display height in pixels      |

## Docker Compose Files

| File                     | Purpose                                                |
| ------------------------ | ------------------------------------------------------ |
| `docker-compose.yml`     | Production - VNC disabled, minimal ports exposed       |
| `docker-compose.dev.yml` | Development - All VNC features enabled, source mounted |

## Ports

| Port | Service                      | Available In |
| ---- | ---------------------------- | ------------ |
| 8080 | HTTP (Nginx)                 | Both         |
| 5901 | VNC - Display :1 (Desktop)   | Dev only     |
| 5999 | VNC - Display :99 (GoExport) | Dev only     |
| 6080 | noVNC - Display :1           | Dev only     |
| 6099 | noVNC - Display :99          | Dev only     |
| 3306 | MySQL                        | Dev only     |
| 6379 | Redis                        | Dev only     |

## Troubleshooting

### Viewing what GoExport sees

1. Run with dev compose: `docker compose -f docker-compose.dev.yml up --build`
2. Open http://localhost:6099/vnc.html in your browser
3. You'll see the actual display :99 where GoExport renders

### Checking logs

```bash
# All supervisor logs
docker exec goexport-server tail -f /var/log/supervisor/*.log

# Specific service
docker exec goexport-server tail -f /var/log/supervisor/xorg.log
docker exec goexport-server tail -f /var/log/supervisor/x11vnc-display99.log
```

## License

[MIT license](https://opensource.org/licenses/MIT)
