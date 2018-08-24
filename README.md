# Composer build docker-compose

Builds a docker-compose.yml file from composer

## Usage

Add the docker-compose settings into extra as follows:

```json

"extra": {
  "docker-compose": {
    "compose-file": "docker-compose.yml",
    "compose": {
      "version": "3",
      "services": {
        "php": {
          "container_name": "my_php",
          "image": "php",
          "expose": [
            "9001"
          ],
          "environment": {
            "PHP_XDEBUG_ENABLED": 1,
            "XDEBUG_CONFIG": "remote_enable=1 remote_mode=req remote_port=9001 remote_connect_back=0 remote_host={{ HOST_IP }}"
          }
        }
      }
    }
  }
}
```

This will build out the docker-compose.yml file 

## Templating 

You can inject the host computer's IP address to allow for xdebug to connect 