# Nezaniel.ComponentView

> A view based on self-rendering presentational components for [Neos CMS](https://neos.io)

**NOTE:** This package is still under development! Please do not use it in production. Expect there to be unannounced breaking changes.

## Setup

As of this writing, the component view package is not yet published on [packagist.org](https://packagist.org). This means, you'll need to add this git repository as a `repository` to your composer.json:

```jsonc
{
    // ...
    "repositories": {
        "nezaniel/componentview": {
            "type": "git",
            "url": "https://github.com/nezaniel/Nezaniel.ComponentView.git"
        }
    }
}
```

After this, you can install the package via composer:

```sh
composer require nezaniel/componentview@2.0.x-dev
```

## Configuration

Once installed, `Nezaniel.ComponentView` hooks itself into the Neos CMS rendering layer. If you open the home page of your site, you'll be greeted by an error message:

> Missing page factory in package Vendor.Site

This means, the package has successfully hooked into the rendering layer and is working properly.

`Nezaniel.ComponentView` is looking for a **page factory class** inside the site package that contains the NodeType of the document that is to be rendered. If that package is called `Vendor.Site`, then the component view will try to instatiate the class `Vendor\Site\Integration\PageFactory`.

This class needs to be created. A minimal implementation may look like this:
```php
<?php

/*
 * This file is part of the Vendor.Site package.
 */

namespace Vendor\Site\Integration;

use Neos\Flow\Annotations as Flow;
use Nezaniel\ComponentView\Application\ComponentViewRuntimeVariables;
use Nezaniel\ComponentView\Application\PageFactoryInterface;
use Nezaniel\ComponentView\Domain\ComponentInterface;

#[Flow\Scope("singleton")]
final class PageFactory implements PageFactoryInterface
{
    public function forDocumentNode(ComponentViewRuntimeVariables $runtimeVariables): ComponentInterface
    {
        return new Page(
            title: $runtimeVariables->documentNode->getProperty('title')
        );
    }
}

```

Of course, given this implementation, we'll also need the `Page` component. We can put a simple implementation just alongside the `PageFactory` class:
```php
<?php

/*
 * This file is part of the Vendor.Site package.
 */

namespace Vendor\Site\Integration;

use Neos\Flow\Annotations as Flow;
use Nezaniel\ComponentView\Domain\AbstractComponent;

#[Flow\Proxy(false)]
final readonly class Page extends AbstractComponent
{
    public function __construct(
        public string $title,
    ) {
    }

    public function render(): string
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $this->title . '</title>
</head>
<body>
    <h1>The Component View is Working</h1>
</body>
</html>
';
    }
}

```

> [!NOTE]
> It'd be sufficient to implement the `ComponentInterface`, e.g.:
```php
use Nezaniel\ComponentView\Domain\ComponentInterface;

#[Flow\Proxy(false)]
final readonly class Page implements ComponentInterface
```
> However, extending the `AbstractComponent` class is the recommended way for writing components. Using `AbstractComponent`, you'll only need to implement the `render` method of your component.

> [!IMPORTANT]
> Be sure to add `#[Flow\Proxy(false)]` to your component class. Flow Framework will otherwise attempt to build a proxy class from your component, which may lead to weird behavior.

This configuration should now display the H1 `The Component View is Working` in your browser.

## License

See [LICENSE](./LICENSE)
