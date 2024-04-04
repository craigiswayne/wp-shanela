# Contributing

* clone down repo
* create new branch

* update your parent composer package with


```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../wp-shanela"
    }
  ],
  "require": {
    "craigiswayne/wp-shanela": "dev-BRANCHNAME"
  },
  "extra": {
    "wp-shanela": {
      "removeDefaultThemes": false
    }
  },
  "config": {
    "allow-plugins": {
      "craigiswayne/wp-shanela": true
    }
  }
}

```
