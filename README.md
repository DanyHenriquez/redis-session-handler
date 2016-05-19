# Redis Session Handler

A pure Php Redis session handler. no extensions needed. It implements the SessionHandlerInterface.

## Usage

Call init with host, port and password. It will register and start the session by itself.
```php
\Prezto\RedisSessionHandler::init('10.241.25.226', '6379', 'aslkjkrnflawekrmgfslerm');
```
