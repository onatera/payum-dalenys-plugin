### Installation

1. Add custom repository in `composer.json` : 

```
{
     "repositories": [
            {
                "type": "vcs",
                "url": "https://github.com/onatera/payum-dalenys-plugin.git"
            },
        ],
}
```

2. Install package : 

```
composer require onatera/payum-dalenys-plugin
```

3. Import package configuration : 

```
imports:
    - { resource: "@OnateraPayumDalenysPlugin/Resources/config/config.yaml" }
```
