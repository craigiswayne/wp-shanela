## Shanela
Moves the wordpress core files to the root of your project

## Usage
```
composer require craigiswayne/wp-shanela
```

### Options
You have 2 options that you can configure:
* removeDefaultThemes (default: true)
* removeDefaultPlugins (default: true)

```
{
    "extra": {
        "wp-shanela": {
            "removeDefaultThemes": false,
            "removeDefaultPlugins": false
        }
    }
}
```

### Resources
1. **https://www.masterzendframework.com/series/tooling/composer/automation-scripts/**
1. https://pantheon.io/blog/writing-composer-scripts
1. [Composer API](https://github.com/composer/composer)
